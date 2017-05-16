<?php

namespace App\Formatters;

use Carbon\Carbon;
use EasyRdf_Serialiser_JsonLd as JsonLdSerialiser;

date_default_timezone_set('Europe/Brussels');

/**
 * Provides functionality to parse Calendar objects into ICAL objects
 * and provides a means to format the outcome of the ICAL events into text, json-ld, html and json
 *
 * TODO split functionality of parsing calendar into ICAL and formatting schedules
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
    protected function formatWeek($serviceId, $format = 'array', $channel = '', $startDate = null)
    {
        if (empty($startDate)) {
            $startDate = Carbon::now();
        }

        $data = $this->renderWeek($serviceId, $channel, $startDate);

        switch ($format) {
            case 'html':
                return $this->makeHtmlForSchedule($data);
                break;
            case 'text':
                return $this->makeTextForSchedule($data);
                break;
            case 'json-ld':
                $serviceUri = createServiceUri($serviceId);
                return $this->makeJsonLdForSchedule($data, $serviceUri);
                break;
            default:
                return $data;
                break;
        }
    }

    /**
     * Render a week schedule for a service and a channel (optional)
     *
     * @param  int    $serviceId
     * @param  string $channel
     * @param  Carbon $startDate
     * @return array
     */
    protected function renderWeek($serviceId, $channel = '', $startDate = null)
    {
        if (empty($startDate)) {
            $startDate = Carbon::now();
        }

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
            throw new \Exception('Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $openinghours = [];

        foreach ($channels as $channel) {
            $weekSchedule = $this->renderWeekForChannel($service['uri'], $channel, $startDate);

            $openinghours[$channel] = $weekSchedule;
        }

        return $openinghours;
    }

    /**
     * Render a schedule into HTML based on an array structure
     *
     * @param  array  $schedule
     * @return string
     */
    protected function makeHtmlForSchedule($data)
    {
        $formattedSchedule = '<div>';

        foreach ($data as $channel => $schedule) {
            $formattedSchedule .= "<span><h4>$channel</h4>";

            if (! empty($schedule)) {
                if (is_array($schedule)) {
                    foreach ($schedule as $entry) {
                        $formattedSchedule .= "<p>$entry</p>";
                    }
                } else {
                    $formattedSchedule .= "<p>$schedule</p>";
                }
            }
        }

        $formattedSchedule .= '</div>';

        return $formattedSchedule;
    }

    /**
     * Return a JSON-LD formatted openinghours schedule
     * TODO: rework how a schedule is returned, some formats
     * need more basic info of the openinghours instead of
     * formatted hours per day, such as this one.
     *
     * @param  array  $data
     * @param  string $serviceUri
     * @return string
     */
    protected function makeJsonLdForSchedule($data, $serviceUri)
    {
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph = new \EasyRdf_Graph();
        $service = $graph->resource($serviceUri, 'schema:Organization');

        // get a raw render for the week:
        // $channel id + days index in english
        // for each channel create an openinghours specification
        // where the channel URI is also set as some sort of context

        foreach ($data as $channelName => $schedule) {
            $channel = app('ChannelRepository')->getByName($serviceUri, $channelName);

            if (empty($channel)) {
                \Log::error('No channel was found for name:' . $channelName . ' and URI ' . $serviceUri);

                continue;
            }

            $channelSpecification = $graph->newBNode(createChannelUri($channel['id']), 'cv:Channel');
            $channelSpecification->addLiteral('schema:label', $channelName);
            $channelSpecification->addLiteral('schema:openingHours', $this->makeTextForDayInfo($schedule));

            $channelSpecification->addResource('cv:isOwnedBy', $service);
        }

        $serialiser = new JsonLdSerialiser();

        return $serialiser->serialise($graph, 'jsonld');
    }

    /**
     * Create a readable text form of a week schedule
     *
     * @deprecated
     * @param  array  $data
     * @return string
     */
    protected function makeTextForSchedule($data)
    {
        $text = '';

        foreach ($data as $channel => $info) {
            $text .= $channel . ': ' . PHP_EOL;

            $text .= $this->makeTextForDayInfo($info);

            $text .= PHP_EOL . PHP_EOL;
        }

        $text = rtrim($text, PHP_EOL);

        return $text;
    }

    /**
     * Print a textual representation of a day schedule
     *
     * @param  string|array $dayInfo
     * @return string
     */
    protected function makeTextForDayInfo($dayInfo)
    {
        $text = '';

        if (is_array($dayInfo)) {
            foreach ($dayInfo as $day) {
                $text .= $day . PHP_EOL;
            }
        } else {
            $text .= $dayInfo . PHP_EOL;
        }

        return $text;
    }

    /**
     * Return the week schedule for a service and channel
     *
     * @param  string $serviceUri
     * @param  string $channel
     * @return array
     */
    protected function renderWeekForChannel($serviceUri, $channel, $startDate)
    {
        // Check if the service and channel exist
        $openinghours = app('OpeninghoursRepository')->getAllForServiceAndChannel($serviceUri, $channel);

        if (empty($openinghours)) {
            abort(404, 'Het gevraagde kanaal heeft geen openingsuren binnen de gevraagde dienst.');
        }

        $weekDays = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];

        // Go to startDate and iterate over every day of the week after that
        // then check if there are events for that given day in the calendar, by priority
        $week = [];

        $endDate = clone $startDate;
        $endDate->addWeek();

        $ical = $this->createIcalForServiceAndChannel($serviceUri, $channel, $startDate, $endDate);
        for ($day = 0; $day <= 6; $day++) {
            $extractedDayInfo = $this->extractDayInfo($ical, $startDate->toDateString(), $startDate->toDateString());

            $dayInfo = 'Gesloten';

            if (! empty($extractedDayInfo)) {
                $dayInfo = $extractedDayInfo;
            }

            $week[$startDate->dayOfWeek] = $dayInfo;

            $startDate->addDay();
        }

        $schedule = [];

        foreach ($week as $dayIndex => $daySchedule) {
            $schedule[] = $weekDays[$dayIndex] . ': ' . $daySchedule;
        }

        return $schedule;
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
    protected function createIcalFromCalendar($calendar, $minTimestamp = null, $maxTimestamp = null)
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

            if (! empty($maxTimestamp) && $event->until > $maxTimestamp->toDateString() && $maxTimestamp > $event->start_date) {
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
                $icalString .= 'RRULE:' . $event->rrule . ';UNTIL=' . $until->format('YmdTHis') . "\n";

                // Build a UID based on priority and closinghours, make sure the priority is numeric and positive
                // the layers can range from -13 to 13 as priority values because of the maximum of 12 layers in the front-end
                $closed = $calendar->closinghours == 0 ? 'OPEN' : 'CLOSED';
                $priority = (int)$calendar->priority + 100;

                $icalString .= 'UID:' . 'PRIOR_' . ((int)$calendar->priority + 100) . '_' . $closed . '_CAL_' . $calendar->id . "\n";
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
        return $date->format('YmdTHis');
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
        return $date->format('YmdTHis');
    }

    /**
     * Sort events by their UID, where the priority of the calendar
     * where the event is part of, is concatenated into
     *
     * @param  array $events
     * @return array
     */
    protected function sortEvents($events)
    {
        return collect($events)->sortByDesc(function ($event) {
            // Parse the priority from the UID
            /*preg_match('#PRIOR_(\d{1,})_.*#', $event->uid, $matches);

            $priority = 0;

            if (! empty($matches[1])) {
                $priority = $matches[1];
            }*/

            return $event->uid;
        })->toArray();
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
    protected function extractDayInfo($ical, $start, $end)
    {
        $events = $ical->eventsFromRange($start, $end);
        $events = $this->sortEvents($events);

        if (empty($events)) {
            return '';
        }

        $hours = [];

        // Make sure we only get hours of the same calendar
        $uid = '';

        foreach ($events as $event) {
            // Make sure we only get hours of the same calendar
            if (! empty($uid) && $event->uid != $uid) {
                break;
            }

            // If closinghours are passed, "closed" is always the last value
            if (str_contains($event->uid, 'CLOSED')) {
                $hours[] = 'Gesloten';
                break;
            } else {
                $start = str_replace('CEST', 'T', $event->dtstart);
                $end =  str_replace('CEST', 'T', $event->dtend);

                $start = str_replace('CET', 'T', $start);
                $end = str_replace('CET', 'T', $end);

                $dtStart = Carbon::createFromFormat('Ymd\THis', $start);
                $dtEnd = Carbon::createFromFormat('Ymd\THis', $end);

                // Check for the one-off chance that there are overlapping hours (=events)
                // within one layer (=calendar)
                if (! empty($hours)) {
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

        return rtrim(implode($hours, ', '), ',');
    }
}
