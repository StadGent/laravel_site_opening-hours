<?php

namespace App\Services;

use App\Services\Contracts\Service;
use Carbon\Carbon;

/**
 * Internal Business logic Service for ICal
 *
 * code is not reviewed
 * deeper analyses  and perhaps refactoring is needed here
 *
 * @todo  this service has been pulled out of original App\Formatters\FormatsOpeninghours trait
 */
class ICalService
{

    /**
     * Singleton class instance.
     *
     * @var ICalService
     */
    private static $instance;

    /**
     * Private contructor for Singleton pattern
     */
    private function __construct() {}

    /**
     * GetInstance for Singleton pattern
     * 
     * @return ICalService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new ICalService();
        }

        return self::$instance;
    }

    /**
     * Create an Ical object based on the service URI, channel and time range
     * by fetching the relevant openinghours objects and parsing the related calendars
     *
     * @param  string $serviceUri
     * @param  string $channel
     * @param  Carbon $startDate
     * @param  Carbon $endDate
     * @return ICal
     */
    protected function createIcalForServiceAndChannel($serviceUri, $channel, $startDate, $endDate)
    {
        $relevantOpeninghours = app('OpeninghoursRepository')->getForServiceAndChannel($serviceUri, $channel, $startDate, $endDate);

        $calendars = collect([]);

        foreach ($relevantOpeninghours as $relevantOpeninghour) {
            $calendars = $calendars->merge($relevantOpeninghour->calendars);
        }

        $calendars = array_sort($calendars, function ($calendar) {
            return $calendar['priority'];
        });

        return $this->createIcalFromCalendars($calendars, $startDate, $endDate);
    }

    /**
     * Create ICal from a calendar object
     *
     * @param Calendar $calendar
     * @param Carbon   $minTimestamp Optional, the min timestamp of the range to create the ical for, used for performance
     * @param Carbon   $maxTimestamp Optional, the max timestamp of the range to create the ical for, used for performance
     *
     * @return ICal
     */
    public function createIcalFromCalendar($calendar, $minTimestamp = null, $maxTimestamp = null)
    {
        $icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";
        $icalString .= $this->createIcalEventStringFromCalendar($calendar, $minTimestamp, $maxTimestamp);
        $icalString .= 'END:VCALENDAR';

        $ical = new \ICal\ICal();
        $ical->initString($icalString);

        return $ical;
    }

    /**
     * Create ICal from calendars object
     *
     * @param Calendar $calendar
     * @param Carbon   $minTimestamp Optional, the min timestamp of the range to create the ical for, used for performance
     * @param Carbon   $maxTimestamp Optional, the max timestamp of the range to create the ical for, used for performance
     *
     * @return ICal
     */
    protected function createIcalFromCalendars($calendars, $minTimestamp, $maxTimestamp)
    {
        $icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";

        foreach ($calendars as $calendar) {
            $icalString .= $this->createIcalEventStringFromCalendar($calendar, $minTimestamp, $maxTimestamp);
        }

        $icalString .= 'END:VCALENDAR';

        $ical = new \ICal\ICal();
        $ical->initString($icalString);

        return $ical;
    }

    /**
     * Create an ICAL string from a calendar
     * @param  Calendar $calendar
     * @return string
     */
    protected function createIcalEventStringFromCalendar($calendar, $minTimestamp = null, $maxTimestamp = null)
    {
        $icalString = '';

        foreach ($calendar->events as $event) {
            $until = $event->until;

            if (!empty($maxTimestamp) && $event->until > $maxTimestamp->toDateString() && $maxTimestamp > $event->start_date) {
                $until = $maxTimestamp->toDateString();
            }

            if ($until >= $minTimestamp->toDateString() || empty($minTimestamp)) {
                // Performance tweak
                $startDate = new Carbon($event->start_date);
                $endDate = new Carbon($event->end_date);
                $untilDate = Carbon::createFromFormat('Y-m-d', $event->until);

                if ($endDate->toDateString() > $until && $endDate->toDateString() > $startDate->toDateString()) {
                    $untilDate = Carbon::createFromFormat('Y-m-d', $event->until);
                    $endDate->month = $untilDate->month;
                } elseif ($endDate->toDateString() < $startDate->toDateString()) {
                    $endDate->month = $startDate->month;
                }

                $until = Carbon::createFromFormat('Y-m-d', $until)->endOfDay();

                $startDate = $this->convertCarbonToIcal($startDate);
                $endDate = $this->convertCarbonToIcal($endDate);

                $icalString .= "BEGIN:VEVENT\n";
                $icalString .= 'DTSTART;TZID=Europe/Brussels:' . $startDate . "\n";
                $icalString .= 'DTEND;TZID=Europe/Brussels:' . $endDate . "\n";
                $icalString .= 'RRULE:' . $event->rrule . ';UNTIL=' . $until->format('Ymd\THis') . "\n";

                // Build a UID based on priority and closinghours, make sure the priority is numeric and positive
                // the layers can range from -13 to 13 as priority values because of the maximum of 12 layers in the front-end
                $closed = $calendar->closinghours == 0 ? 'OPEN' : 'CLOSED';

                $icalString .= 'UID:' . 'PRIOR_' . ((int) $calendar->priority + 99) . '_' . $closed . '_CAL_' . $calendar->id . "\n";
                $icalString .= "END:VEVENT\n";
            }
        }

        return $icalString;
    }

    /**
     * Format a Carbon date to YYYYmmddThhmmss
     *
     * @param  Carbon $date
     * @return string
     */
    protected function convertCarbonToIcal($date)
    {
        return $date->format('Ymd\THis');
    }

    /**
     * Format an ISO date to YYYYmmddThhmmss
     *
     * @param  string $date
     * @return string
     */
    protected function convertIsoToIcal($date)
    {
        $date = new Carbon($date);

        return $date->format('Ymd\THis');
    }

    /**
     * Check if there are events for a given day
     * Use the UIDs to get information on which calendar the events are from
     * (e.g. closed or open calendar)
     *
     * @param  ICal   $ical
     * @param  string $start date string YYYY-mm-dd
     * @param  string $end   date string YYYY-mm-dd
     * @return array
     */
    public function extractDayInfo($ical, $start, $end)
    {
        // Get the events from the calendar for the given range and
        // sort them by priority, after that only keep those of the highest priority
        $events = $ical->eventsFromRange($start, $end);

        usort($events, function ($a, $b) {
            return strcmp($a->uid, $b->uid);
        });

        if (empty($events)) {
            return '';
        }

        $hours = [];

        // Make sure we only get hours of the same calendar, with the highest priority
        // make sure to use collect/first because the keys will be switched as well,
        // so fetching events[0] will result in fetching the value with key 0, not the first element per se
        $uid = collect($events)->first()->uid;

        $events = collect($events)->filter(function ($event) use ($uid) {
            return $event->uid == $uid;
        })->toArray();

        usort($events, function ($a, $b) {
            return strcmp($a->dtstart, $b->dtstart);
        });

        foreach ($events as $event) {
            // Make sure we only get hours of the same calendar
            if (!empty($uid) && $event->uid != $uid) {
                break;
            }

            // If closinghours are passed, "closed" is always the last value
            if (str_contains($event->uid, 'CLOSED')) {
                return 'Gesloten';
            } else {
                $start = $event->dtstart;
                $end = $event->dtend;

                $dtStart = Carbon::createFromFormat('Ymd\THis', $start);
                $dtEnd = Carbon::createFromFormat('Ymd\THis', $end);

                // Check for the one-off chance that there are overlapping hours (=events)
                // within one layer (=calendar)
                if (!empty($hours)) {
                    $lastHourRange = end($hours);

                    $lastHourRangeEnd = explode('-', $lastHourRange);

                    if (trim(array_get($lastHourRangeEnd, 1, '')) < $dtStart->format('H:i')) {
                        $hours[] = $dtStart->format('H:i') . ' - ' . $dtEnd->format('H:i');
                    }
                } else {
                    $hours[] = $dtStart->format('H:i') . ' - ' . $dtEnd->format('H:i');
                }
            }

            if (empty($uid)) {
                $uid = $event->uid;
            }
        }

        $hours = array_unique($hours);

        // return rtrim(implode($hours, ', '), ',');

        return $hours;
    }
}
