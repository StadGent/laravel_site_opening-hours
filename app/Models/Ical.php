<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use \ICal\ICal as ICalParser;

/**
 * Model to keep specific Ical object per Openinghours
 * Can be called by openinghours->ical()
 * Is bound with IcalParser
 * Uses limited Ical string date range to limit expensive overeagerness of parser *
 */
class Ical
{
    /**
     * @var \ICal\ICal
     */
    private $parser;

    /**
     * @var string
     */
    private $icalString;

    /**
     * @var Collection
     */
    private $calendars;

    /**
     * @param Collection $calendars
     */
    public function __construct(Collection $calendars)
    {
        $calendars = array_sort($calendars, function ($calendar) {
            return $calendar['priority'];
        });
        $this->calendars = $calendars;

        return $this;
    }

    /**
     * Create parser within from until range
     *
     * For performance reasons it is better to init the parser only
     * for the from until periode that will be needed
     * The parser does a processDateConversions
     * that converts all the dates and this is expensive
     *
     * @param Carbon   $from
     * @param Carbon   $until
     *
     * @return ICal
     */
    public function initParser()
    {
        $this->parser = new ICalParser();
        $this->parser->initString($this->icalString);
    }

    /**
     * @param Carbon $from
     * @param Carbon $till
     */
    public function createIcalString(Carbon $from, Carbon $till, $initParser = true)
    {
        $this->icalString = "BEGIN:VCALENDAR\nVERSION:2.0\nCALSCALE:GREGORIAN\n";

        foreach ($this->calendars as $calendar) {
            $this->icalString .= $this->createIcalEventStringFromCalendar($calendar, $from, $till);
        }

        $this->icalString .= 'END:VCALENDAR';
        if ($initParser) {
            $this->initParser();
        }

        return $this;
    }

    /**
     * @return ICal string
     */
    public function getIcalString()
    {
        return $this->icalString;
    }

    /**
     * Create an ICAL string from a calendar
     *
     * @param  Calendar    $calendar     [description]
     * @param  Carbon|null $minTimestamp [description]
     * @param  Carbon|null $maxTimestamp [description]
     * @return [type]                    [description]
     */
    protected function createIcalEventStringFromCalendar(
        Calendar $calendar,
        Carbon $minTimestamp = null,
        Carbon $maxTimestamp = null
    ) {
        $icalString = '';

        foreach ($calendar->events as $event) {
            $until = new Carbon($event->until);

            if (!empty($maxTimestamp) && $event->until > $maxTimestamp && $maxTimestamp > $event->start_date) {
                $until = $maxTimestamp;
            }

            if ($until >= $minTimestamp || empty($minTimestamp)) {
                // Performance tweak
                $startDate = new Carbon($event->start_date);
                $endDate = new Carbon($event->end_date);

                $startDate->day = $minTimestamp->day;
                $startDate->month = $minTimestamp->month;
                $startDate->year = $minTimestamp->year;
                $startDate->subDay(2);

                $endDate->day = $startDate->day;
                $endDate->month = $startDate->month;
                $endDate->year = $startDate->year;

                $status = 'OPEN';
                if ($calendar->closinghours === 1) {
                    $status = 'CLOSED';
                    $startDate->hour = 0;
                    $startDate->minute = 0;
                    $endDate->hour = 23;
                    $endDate->minute = 59;
                }

                $icalString .= "BEGIN:VEVENT\n";
                $icalString .= 'SUMMARY:' . $calendar->label . "\n";
                $icalString .= 'STATUS:' . $status . "\n";
                $icalString .= 'PRIORITY:' . ($calendar->priority + 20) . "\n";
                $icalString .= 'DTSTART:' . $startDate->format('Ymd\THis') . "\n";
                $icalString .= 'DTEND:' . $endDate->format('Ymd\THis') . "\n";
                $icalString .= 'DTSTAMP:' . Carbon::now()->format('Ymd\THis') . 'Z' . "\n";
                $icalString .= 'RRULE:' . $event->rrule . ';UNTIL=' . $until->endOfDay()->format('Ymd\THis') . "\n";
                $icalString .= 'UID:' . 'PRIOR_' . ((int) $calendar->priority + 99) . '_' . $status . '_CAL_' ;
                $icalString .=  $calendar->id . "\n";
                $icalString .= "END:VEVENT\n";
            }
        }

        return $icalString;
    }

    /**
     * Check if there are events for a given day
     * Attr openNow respects the given time and adds an extra minute
     *
     * @param  Carbon  $date    [description]
     * @param  boolean $openNow [description]
     * @return [type]           [description]
     */
    public function getDayInfo(Carbon $date, $openNow = false)
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        if (empty($this->icalString)) {
            $this->initParser($startDate, $endDate);
        }

        if ($openNow) {
            $startDate = $date->copy();
            $endDate = $date->copy()->addMinute();
        }

        $dayInfo = new DayInfo($startDate);
        $events = $this->parser->eventsFromRange($startDate, $endDate);
        foreach ($events as $event) {
            if ($dayInfo->open === false) {
                continue;
            }

            $dayInfo->open = false;
            if ($event->status === 'OPEN') {
                $dayInfo->open = true;
                $start = $event->dtstart;
                $end = $event->dtend;

                $dtStart = Carbon::createFromFormat('Ymd\THis', $start);
                $dtEnd = Carbon::createFromFormat('Ymd\THis', $end);

                $dayInfo->hours[] = ['from' => $dtStart->format('H:i'), 'until' => $dtEnd->format('H:i')];
            }
        }

        if ($dayInfo->open === null) {
            $dayInfo->open = false;
        }

        return $dayInfo;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->icalString;
    }
}
