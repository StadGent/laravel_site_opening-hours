<?php

namespace App\Console\Commands;

use App\Models\DefaultCalendar;
use App\Models\DefaultEvent;

/**
 * Fetch the holidays for GENT and import them
 *
 * In first stage: use files with ical data
 * In second stage: import ical data from LOD
 * Also only use nl_BE for now, multi lang is no prio yet
 */
class FetchODHolidays extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:fetch-od-holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Open Data holidays data';

    /**
     * @var array
     */
    private $supportedCalendars = [];

    /**
     * Create a new command instance.
     *
     * $this->supportedLangs = array_keys(config('app.locale_date_time_formats'));
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->supportedCalendars = config('app.supported_exception_callendars');
    }

    /**
     * Execute the console command.
     *
     * By ticket OPEN-99 the data will be read out files and imported
     * By ticket OPEN-97 the data will be collected from (L)OD and imported
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Init handle FetchODHolidays');
        foreach ($this->supportedCalendars as $calendarName => $fileLocation) {
            try {
                // check on calendar
                $calendar = $this->findOrCreateCalendar($calendarName);
                $this->comment('Sync holiday data for ' . $calendarName);

                $ical = file_get_contents($fileLocation);
                $this->importCalendar($calendar, $ical);
            } catch (\Exception $e) {
                $this->error('Failed to sync holiday data for ' . $calendarName . "\n" . $e->getMessage());
            }
        }
    }

    /**
     * check data and save in db
     *
     * @param string $calendar$calendarNam
     * @param string $ical
     *
     * @return void
     */
    private function importCalendar(DefaultCalendar $calendar, $ical)
    {
        $touchedEvents = [];
        // implode ical into events
        $events = explode('BEGIN:VEVENT', $ical);
        // get rid of the  BEGIN:VCALENDAR boilerplate
        array_shift($events);
        // loop over events
        foreach ($events as $event) {
            $event = $this->findOrCreateEvent($calendar, $event);
            $touchedEvents[] = $event->id;
        }
        $this->cleanupCalendarEvents($calendar, $touchedEvents);
    }

    /**
     * Find or create the calendar
     *
     * @param string $calendarLabel
     *
     * @return DefaultCalendar
     */
    private function findOrCreateCalendar($calendarLabel)
    {
        $calendar = DefaultCalendar::where('label', $calendarLabel)->first();
        if (!isset($calendar->id)) {
            $calendar = DefaultCalendar::create(['label' => $calendarLabel]);
            $calendar->save();
        }

        return $calendar;
    }

    /**
     * @param DefaultCalendar $calendar
     * @param $eventString
     */
    private function findOrCreateEvent(DefaultCalendar $calendar, $eventString)
    {
        $eventProps = explode(PHP_EOL, $eventString);

        $rrule = '';
        $startDate = '';
        $endDate = '';
        $label = '';

        foreach ($eventProps as $propline) {
            if (strpos($propline, ':') === false) {
                continue;
            }
            list($prop, $value) = explode(':', $propline);
            $value = trim($value);
            // use strlen of SUMMARY without the locale
            switch (substr($prop, 0, strlen('SUMMARY;LANGUAGE='))) {
                case 'DTEND;VALUE=DATE':
                    $endDate = date('Y-m-d 23:59:59', strtotime($value));
                    break;
                case 'DTSTART;VALUE=DAT':
                    $startDate = date('Y-m-d 00:00:00', strtotime($value));
                    break;
                case 'RRULE':
                    $rrule = $value;
                    break;
                case 'SUMMARY;LANGUAGE=':
                    $label = $value;
                    break;
            }
        }

        if (empty($rrule) && empty($endDate)) {
            $endDate = substr($startDate, 0, -9) . ' 23:59:59';
        }

        $event = $calendar->events()
            ->where('label', $label)
            ->where('rrule', $rrule)
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->where('calendar_id', $calendar->id)
            ->first();

        if (!isset($event->id)) {
            $this->info('New event ' . $label . ' ' . $startDate . ' for ' . $calendar->label);
            $event = DefaultEvent::create([
                'rrule' => $rrule,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'calendar_id' => $calendar->id,
                'label' => $label,

            ]);
        }

        return $event;
    }

    /**
     * Clean up the events from a calendar that did not pass by the checks
     *
     * Also known as removed from the source
     *
     * @param DefaultCalendar $calendar
     * @param  array $touchedEvents
     *
     * @return void
     */
    private function cleanupCalendarEvents(DefaultCalendar $calendar, $touchedEvents)
    {
        $notUsedEvents = $calendar->events()->whereNotIn('id', $touchedEvents);
        $notUsedEvents->each(function (DefaultEvent $event) {
            $msg = 'Event removed' . $event->label . ' ' . $event->start_date . ' from ' . $event->calendar->label;
            $this->info($msg);
        });
        $notUsedEvents->delete();
    }
}
