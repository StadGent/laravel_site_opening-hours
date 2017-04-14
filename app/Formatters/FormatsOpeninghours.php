<?php

namespace App\Formatters;

use Carbon\Carbon;
use EasyRdf_Serialiser_JsonLd as JsonLdSerialiser;

date_default_timezone_set('Europe/Brussels');

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
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
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

        // Get the openinghours that is active now
        $relevantOpeninghours = '';

        foreach ($openinghours as $openinghoursInstance) {
            if ($startDate->between(
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

        // Go to startDate and iterate over every day of the week after that
        // then check if there are events for that given day in the calendar, by priority
        //$startDate = Carbon::now();

        $week = [];

        for ($day = 0; $day <= 6; $day++) {
            $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                return $calendar->priority;
            });

            // Default status of a day is "Closed"
            $dayInfo = 'Gesloten';

            // Add the max timestamp
            $maxTimestamp = clone $startDate;
            $maxTimestamp = $maxTimestamp->addDay(2);

            $minTimestamp = clone $startDate;
            $minTimestamp = $minTimestamp->subDay(2);

            // Iterate all calendars for the day of the week
            foreach ($calendars as $calendar) {
                $ical = $this->createIcalFromCalendar($calendar, $minTimestamp, $maxTimestamp);

                $extractedDayInfo = $this->extractDayInfo($ical, $startDate->toDateString(), $startDate->toDateString());

                if (! empty($extractedDayInfo)) {
                    $dayInfo = $calendar->closinghours ? 'Gesloten' : $extractedDayInfo;

                    break;
                }
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
     * Create ICal from a calendar object
     *
     * @param Calendar $calendar
     * @param Carbon   $minTimestamp Optional, the min timestamp of the range to create the ical for, used for performance
     * @param Carbon   $maxTimestamp Optional, the max timestamp of the range to create the ical for, used for performance
     *
     * Note: possible performance tweak is to edit the start/enddate so that the amount of events remains small for long lasting recurrences
     * CAVEAT: make sure that the start/enddate is edit correctly to avoid unwanted behaviour, e.g. if the startdate was originally on a monday
     * keep it on a monday as close as possible to the min/max range.
     *
     * @return ICal
     */
    protected function createIcalFromCalendar($calendar, $minTimestamp = null, $maxTimestamp = null)
    {
        $icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";

        foreach ($calendar->events as $event) {
            /*$startDate = $this->convertIsoToIcal($event->start_date);
            $endDate = $this->convertIsoToIcal($event->end_date);*/

            $until = $event->until;

            if (! empty($maxTimestamp) && $event->until > $maxTimestamp->toDateString()) {
                $until = $maxTimestamp->toDateString();
            }

            if ($until >= $minTimestamp->toDateString() || empty($minTimestamp)) {
                // Performance tweak
                $startDate = new Carbon($event->start_date);
                $endDate = new Carbon($event->end_date);
                $untilDate = Carbon::createFromFormat('Y-m-d', $event->until);

                if ($startDate < $minTimestamp && $startDate->day <= 28) {
                    $startDate->month = $minTimestamp->month;
                }

                if ($endDate->toDateString() > $until && $endDate->toDateString() > $startDate->toDateString()) {
                    $untilDate = Carbon::createFromFormat('Y-m-d', $event->until);
                    $endDate->month = $untilDate->month;
                }

                $startDate = $this->convertCarbonToIcal($startDate);
                $endDate = $this->convertCarbonToIcal($endDate);
                $until = Carbon::createFromFormat('Y-m-d', $until)->endOfDay();

                $icalString .= "BEGIN:VEVENT\n";
                $icalString .= 'DTSTART;TZID=Europe/Brussels:' . $startDate . "\n";
                $icalString .= 'DTEND;TZID=Europe/Brussels:' . $endDate . "\n";
                $icalString .= 'RRULE:' . $event->rrule . ';UNTIL=' . $until->format('YmdTHis') . "\n";
                $icalString .= 'UID:' . str_random(32) . "\n";
                $icalString .= "END:VEVENT\n";
            }
        }

        $icalString .= 'END:VCALENDAR';

        return new \ICal\ICal(explode(PHP_EOL, $icalString));
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
     * Check if there are events in a given range (day)
     *
     * @param  ICal   $ical
     * @param  string $start date string YYYY-mm-dd
     * @param  string $end   date string YYYY-mm-dd
     * @return array
     */
    protected function extractDayInfo($ical, $start, $end)
    {
        $events = $ical->eventsFromRange($start, $end);

        if (empty($events)) {
            return '';
        }

        $hours = [];

        foreach ($events as $event) {
            $start = str_replace('CEST', 'T', $event->dtstart);
            $end = str_replace('CEST', 'T', $event->dtend);

            $start = str_replace('CET', 'T', $start);
            $end = str_replace('CET', 'T', $end);

            $dtStart = Carbon::createFromFormat('Ymd\THis', $start);
            $dtEnd = Carbon::createFromFormat('Ymd\THis', $end);

            $hours[] = $dtStart->format('H:i') . ' - ' . $dtEnd->format('H:i');
        }

        return rtrim(implode($hours, ', '), ',');
    }
}
