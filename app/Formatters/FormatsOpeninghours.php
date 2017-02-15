<?php

namespace App\Formatters;

use Carbon\Carbon;

/**
 * Returns a textual form of the openinghours of a service
 */
trait FormatsOpeninghours
{
    /**
     * Compute a week schedule for a service
     *
     * @param  int    $serviceId
     * @param  string $channel   The specific channel to print
     * @return array
     */
    private function formatWeek($serviceId, $format = 'array', $channel = '')
    {
        $data = $this->renderWeek($serviceId, $channel);

        switch ($format) {
            case 'html':
                return $this->makeHtmlFromSchedule($data);
                break;
            default:
                return $data;
                break;
        }
    }

    /**
     * Render a week schedule for a service and a channel (optional)
     * @param  int    $serviceId
     * @param  string $channel
     * @return array
     */
    private function renderWeek($serviceId, $channel = '')
    {
        $service = app('ServicesRepository')->getById($serviceId);

        $channels = [];

        // If no channel is passed, return all channels
        if (! empty($channel)) {
            $channels[] = $channel;
        } else {
            foreach ($service['channels'] as $object) {
                $channels[] = $object['label'];
            }
        }

        if (empty($channels)) {
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $openinghours = [];

        foreach ($channels as $channel) {
            $weekSchedule = $this->renderWeekForChannel($service['uri'], $channel);

            $openinghours[$channel] = $weekSchedule;
        }

        return $openinghours;
    }

    /**
     * Create a readable text form of a week schedule
     *
     * @param  array  $data
     * @return string
     */
    private function makeHtmlFromSchedule($data)
    {
        $text = '';

        foreach ($data as $channel => $info) {
            $text .= $channel . ': ' . PHP_EOL;

            if (is_array($info)) {
                foreach ($info as $day) {
                    $text .= $day . PHP_EOL;
                }
            } else {
                $text .= $info . PHP_EOL;
            }

            $text .= PHP_EOL . PHP_EOL;
        }

        $text = rtrim($text, PHP_EOL);

        return $text;
    }

    /**
     * Return the week schedule for a service and channel
     *
     * @param  string $serviceUri
     * @param  string $channel
     * @return array
     */
    private function renderWeekForChannel($serviceUri, $channel)
    {
        // Check if the service and channel exist
        $openinghours = app('OpeninghoursRepository')->getAllForServiceAndChannel($serviceUri, $channel);

        if (empty($openinghours)) {
            abort(404, 'Het gevraagde kanaal heeft geen openingsuren binnen de gevraagde dienst.');
        }

        $weekDays = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];

        // Get the openinghours that is active now
        $relevantOpeninghours = '';

        foreach ($openinghours as $openinghoursInstance) {
            if (Carbon::now()->between(
                (new Carbon($openinghoursInstance->start_date)),
                (new Carbon($openinghoursInstance->end_date))
            )) {
                $relevantOpeninghours = $openinghoursInstance;
                break;
            }
        }

        if (empty($relevantOpeninghours)) {
            // abort(404, 'No relevant openinghours found for this week.');
            return [];
        }

        // Go to the start of the week starting from today and iterate over every day
        // then check if there are events for that given day in the calendar, by priority
        $weekDay = Carbon::now();

        $week = [];

        for ($day = 0; $day <= 6; $day++) {
            $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                return $calendar->priority;
            });

            $dayInfo = 'Gesloten';

            // Iterate all calendars for the day of the week
            foreach ($calendars as $calendar) {
                $ical = $this->createIcalFromCalendar($calendar);

                $extractedDayInfo = $this->extractDayInfo($ical, $weekDay->toDateString(), $weekDay->toDateString());

                if (! empty($extractedDayInfo)) {
                    $dayInfo = $calendar->closinghours ? 'Gesloten' : $extractedDayInfo;

                    break;
                }
            }

            $week[$weekDay->dayOfWeek] = $dayInfo;

            $weekDay->addDay();
        }

        $schedule = [];

        foreach ($week as $dayIndex => $daySchedule) {
            $schedule[] = $weekDays[$dayIndex] . ': ' . $daySchedule;
        }

        return $schedule;
    }

    /**
     * Create ICal from a calendar object
     *
     * @param  Calendar $calendar
     * @return ICal
     */
    private function createIcalFromCalendar($calendar)
    {
        $icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";

        foreach ($calendar->events as $event) {
            $dtStart = $this->convertIsoToIcal($event->start_date);
            $dtEnd = $this->convertIsoToIcal($event->end_date);

            $icalString .= "BEGIN:VEVENT\n";
            $icalString .= 'DTSTART;TZID=Europe/Brussels:' . $dtStart . "\n";
            $icalString .= 'DTEND;TZID=Europe/Brussels:' . $dtEnd . "\n";
            $icalString .= 'RRULE:' . $event->rrule . ';UNTIL=' . $this->convertIsoToIcal($event->until) . "\n";
            $icalString .= 'UID:' . str_random(32) . "\n";
            $icalString .= "END:VEVENT\n";
        }

        $icalString .= 'END:VCALENDAR';

        return new \ICal\ICal(explode(PHP_EOL, $icalString), 'MO');
    }

    /**
     * Format an ISO date to YYYYmmddThhmmss
     *
     * @param string $date
     * @return
     */
    private function convertIsoToIcal($date)
    {
        $date = new Carbon($date);
        $date = $date->format('Ymd His');

        return str_replace(' ', 'T', $date);
    }

    /**
     * Check if there are events in a given range (day)
     *
     * @param  ICal   $ical
     * @param  string $start date string YYYY-mm-dd
     * @param  string $end   date string YYYY-mm-dd
     * @return array
     */
    private function extractDayInfo($ical, $start, $end)
    {
        $events = $ical->eventsFromRange($start, $end);

        if (empty($events)) {
            return '';
        }

        $hours = [];

        foreach ($events as $event) {
            $dtStart = Carbon::createFromTimestamp($ical->iCalDateToUnixTimestamp($event->dtstart));
            $dtEnd = Carbon::createFromTimestamp($ical->iCalDateToUnixTimestamp($event->dtend));

            $hours[] = $dtStart->format('H:i') . ' - ' . $dtEnd->format('H:i');
        }

        return rtrim(implode($hours, ', '), ',');
    }
}
