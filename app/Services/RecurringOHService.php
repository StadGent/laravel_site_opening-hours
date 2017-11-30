<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\Event;
use App\Models\Service;
use App\Services\RecurringOHService;
use Carbon\Carbon;

/**
 *
 */
class RecurringOHService
{
    /**
     * Singleton class instance.
     *
     * @var LocaleService
     */
    private static $instance;

    /**
     * The ID of the service
     * @var int
     */
    private $serviceId;

    /**
     * Start of current periode => NOW->startOfWeek()
     * @var Carbon
     */
    private $startPeriode;

    /**
     * End of current periode => NOW + 3 months
     * @var Carbon
     */
    private $endPeriode;

    /**
     * Start property of active Event record
     * @var Carbon
     */
    private $eventStart;

    /**
     * End property of active Event record
     * @var Carbon
     */
    private $eventEnd;

    /**
     * Until property of active Event record
     * @var Carbon
     */
    private $eventUntil;

    /**
     * Private contructor for Singleton pattern
     * set init periode between now and +3 months
     */
    private function __construct()
    {
        $initStart = Carbon::today()->startOfWeek();
        $this->setStartPeriode($initStart);
        $this->setEndPeriode($initStart->copy()->addMonths(3));
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return ChannelService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Carbon $startPeriode
     */
    public function setStartPeriode(Carbon $startPeriode)
    {
        $this->startPeriode = $startPeriode;
    }

    /**
     * @param Carbon $endPeriode
     */
    public function setEndPeriode(Carbon $endPeriode)
    {
        $this->endPeriode = $endPeriode;
    }

    /**
     * Render the output for a calender in htlm format
     *
     * Take only the OH(s) that are active from now until +3 months
     * for each of its calendar it looks for output
     *
     * @param Service $service
     * @return string text/html
     */
    public function getRecurringOHForService(Service $service)
    {
        $output = '';

        foreach ($service->channels as $channel) {
            // set channel header
            $channelOutput = '';
            // get openinghours that have begin and end overlapping the periode-> calendars + events
            $ohCollection = $channel->openinghours()
                ->where('start_date', '<=', $this->endPeriode)
                ->where('end_date', '>=', $this->startPeriode)
                ->get();
            foreach ($ohCollection as $openinghours) {
                $ohOutput = '';
                // Loop over calendars and add events to output
                $calendarsCollection = $openinghours->calendars()->orderBy('priority', 'desc')->get();
                foreach ($calendarsCollection as $calendar) {
                    $calendarRule = $this->collectForCalendar($calendar);
                    $calendarOutput = $this->clearnUpOutput($calendarRule);

                    if ($calendarOutput !== '') {
                        $ohOutput .= '<div>' . "\n";
                        $ohOutput .= '<h3>' . $calendar->label;
                        // set OH start and end to make distinct titles when +1 OH obj in periode
                        if ($ohCollection->count() > 1) {
                            $ohOutput .= ' ' . $openinghours->start_date . ' ' . $openinghours->end_date;
                        }
                        $ohOutput .= '</h3>' . "\n";
                        $ohOutput .= $calendarOutput;
                        $ohOutput .= '</div>' . "\n";
                    }
                }
                if ($ohOutput !== '') {
                    $channelOutput .= $ohOutput;
                }
            }
            if ($channelOutput !== '') {
                $output .= '<h2>' . $channel->label . '</h2>' . "\n" . $channelOutput;
            }
        }

        return trim($output);
    }

    /**
     * Loop over the events of the calendar
     *
     * Valid events will be renered
     * Output of the event will be collected in the array
     *
     * @param Calendar $calendar
     * @return array
     */
    private function collectForCalendar(Calendar $calendar)
    {
        $calendarRule = [];
        $events = $calendar->events;
        foreach ($events as $event) {
            $output = $this->collectForEvent($event);
            if ($output === false) {
                continue;
            }

            $calendarRule[] = $output;
        }

        return $calendarRule;
    }

    /**
     * Eliminate events not within the periode of next 3 months
     *
     * Event must begin before periode ends and event until must be after start of periode
     * Let me sketch this: (god knows I needed this confusing sh*t visualised)
     * -----------------------------------------------------------------------
     * |  = start/end of periode
     * <  = start event
     * // = end event
     * >  = until event
     * ++ = relevant duration of event in periode
     * -- = irralivant duration of event
     * -------------------------------------
     *            NOW            +3M
     * ------------|++++++++++++++|-------- == PERIODE now -> +3 months
     * ------->    |              |         -- until event < start periode
     * ------------|+++++>        |         ++ until event > start periode
     *  <--//------|--<++//-------|-<--//>  ++ event sequence in periode
     *         <---|++++++++++++++|---->    ++ start event < end periode && until event > start periode
     *             |         <++++|-------- ++ start event < end periode
     *  <--//------|--------------|-<--//>  -- event sequence not in periode => FREQ=YEARLY
     *             |              |  <----- -- start event > end periode
     * -------------------------------------
     * Event end has no meaning as this is only the end of a frequency and could be repeated within the periode
     * FREQ YEARLY will need extra check on overlap in active periode of 3 month
     * FREQ MONTHLY / WEEKLY / DAILY will occure in active periode of 3 months
     *
     * NEVER MIND events without FREQ
     * NEVER MIND events without until
     * NEVER MIND events without end
     *
     * The UI always provides (doesn't make much sence but that's faith for you infidels)
     * When we stop beleving, we start having problems ... (big ones)
     * The UI gives and the UI takes...
     *
     * \Log::debug('EVENT => ' . $event->rrule . ': ' . $this->eventStart . "-> " .
     * $this->eventEnd . " <- " . $this->eventUntil);
     *
     * @param Event $event
     * @return boolean
     */
    public function validateEvent(Event $event)
    {
        $this->eventStart = new Carbon($event->start_date);
        $this->eventEnd = new Carbon($event->end_date);
        $this->eventUntil = new Carbon($event->until);

        // check event until > begin of periode to skip
        if ($this->eventUntil->lt($this->startPeriode)) {
            return false;
        }

        // check event start is later then end of periode to skip
        if ($this->eventStart->gt($this->endPeriode)) {
            return false;
        }

        // FREQ=YEARLY => checkTheFreqYearRrule
        if (strpos($event->rrule, 'FREQ=YEARLY') !== false &&
            !$this->checkTheFreqYearRruleInPeriode()) {
            return false;
        }
        // FREQ MONTHLY / WEEKLY / DAILY will occure in active periode of 3 months

        return true;
    }

    /**
     * Check or event of FREQ=YEARLY overlaps in active periode of now and  next 3 months
     *
     * Adjust event to the year of the startPeriode
     * And the year of the endPeriode when not the same as startPeriode
     *
     * @return boolean
     */
    public function checkTheFreqYearRruleInPeriode()
    {
        $theDifference = $this->eventEnd->year - $this->eventStart->year;
        // take the year of the startPeriode
        $this->eventStart->year = $this->startPeriode->year;
        $this->eventEnd->year = $this->startPeriode->year;
        // the end could be in the next year for example Christmas Holidays
        $this->eventEnd->addYears($theDifference);
        if ($this->eventEnd->gt($this->startPeriode) && $this->eventStart->lt($this->endPeriode)) {
            return true;
        }

        // check on year leap (needed for when request if from December until February you have a 2nd new year to check)
        if ($this->startPeriode->year != $this->endPeriode->year) {
            $this->eventStart->year = $this->endPeriode->year;
            $this->eventEnd->year = $this->endPeriode->year;
            // the end could be in the next year
            $this->eventEnd->addYears($theDifference);
            if ($this->eventEnd->gt($this->startPeriode) && $this->eventStart->lt($this->endPeriode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the Human Readable text of an event
     *
     * @param Event $event
     * @return string
     */
    public function collectForEvent(Event $event)
    {
        if (!$this->validateEvent($event)) {
            return false;
        }
        $eventOutput = '';

        if (!empty($event->rrule)) {
            $rrulePerProp = $this->splitRrule($event->rrule);
        }

        switch ($rrulePerProp['FREQ']) {
            case 'YEARLY':
                $eventOutput = $this->hrYearly();
                break;
            case 'MONTHLY':
            case 'WEEKLY':
                $eventOutput = $this->hrMonthlyAndWeekly($rrulePerProp);
                break;
            case 'DAILY':
            default:
                $eventOutput = $this->hrDaily();
                break;
        }

        $eventOutput .= $this->hrOpenClosedEvent($event->calendar->closinghours);

        return $eventOutput;
    }

    /**
     * Get the Human Readable text of an event with Yearly Frequenty
     *
     * Compile based on event is covering one or multiple days
     * => use eventEND to get last day of single event
     * => use eventUNTIL to get last day of frequenty (so don't use it here)
     * ignore attributes as BYMONTH and BYMONTHDAY as these are in the start_date
     *
     * @return string
     */
    protected function hrYearly()
    {
        if ($this->eventStart->format('Y-m-d') !== $this->eventEnd->format('Y-m-d')) {
            return $this->eventStart->format('d-m-Y') . ' tot ' . $this->eventEnd->format('d-m-Y');
        }

        return 'Op ' . $this->eventStart->format('d-m-Y');
    }

    /**
     * Get the Human Readable text of an event with Monthly or Weekly Frequenty
     *
     * Use the rrule attributes BYSETPOS and BYDAY to compile the sequence info
     *
     * @param array rrulePerProp
     * @return string
     */
    protected function hrMonthlyAndWeekly($rrulePerProp)
    {
        $eventOutput = '';
        if (isset($rrulePerProp['BYSETPOS'])) {
            $eventOutput .= $this->hrBySetPos($rrulePerProp['BYSETPOS']) . ' ';
        }

        if (isset($rrulePerProp['BYDAY'])) {
            $eventOutput .= $this->hrByDay($rrulePerProp['BYDAY']);
        }

        $eventOutput .= ($rrulePerProp['FREQ'] === 'MONTHLY' ? ' van de maand' : '');

        return 'Elke ' . $eventOutput;
    }

    /**
     * Get the Human Readable text of an event with Daily Frequenty
     *
     * Compile based on event is covering one or multiple days
     * => use eventEND to get last day of single event (always same day as eventStart so don't use it here)
     * => use eventUNTIL to get last day of frequenty (that is the full event we need)
     * => in case the event has no until => event of 1 day ... we do use the end
     *
     * @return  string
     */
    protected function hrDaily()
    {
        if ($this->eventStart->format('Y-m-d') !== $this->eventUntil->format('Y-m-d')) {
            return $this->eventStart->format('d-m-Y') . ' tot ' . $this->eventUntil->format('d-m-Y');
        }

        return 'Op ' . $this->eventStart->format('d-m-Y');
    }

    /**
     * Get the Human Readable text of an event that is open or closed
     *
     * @param $isClosed
     * @return string
     */
    protected function hrOpenClosedEvent($isClosed)
    {
        if ($isClosed) {
            return ' gesloten';
        }

        return ': open ' . $this->eventStart->format('H:i') . ' - ' . $this->eventEnd->format('H:i');
    }

    /**
     * Clean up the Output
     *
     * returns empty string when param is empty
     * breaks up the array an put it in a nice html paragraphe with line breaks
     *
     * Does nothing special YET, but good location for:
     * - sorting on start date
     * - clean up duplications that distinct between morning and afternoon hours
     *   could use the method longest_common_substring from https://gist.github.com/chrisbloom7/1021218 for this
     *
     * @param array $calendarRule
     * @return string
     */
    protected function clearnUpOutput($calendarRule)
    {
        if (empty($calendarRule)) {
            return '';
        }

        return '<p>' . implode("<br />\n", $calendarRule) . '</p>' . "\n";
    }

    /**
     * Split the RRule up into props
     *
     * @param $rrime
     * @return mixed
     */
    public function splitRrule($rrule)
    {
        $splitRrule = explode(';', $rrule);
        $rrulePerProp = [];
        foreach ($splitRrule as $prop) {
            $cut = explode('=', $prop);
            $rrulePerProp[$cut[0]] = $cut[1];
        }

        return $rrulePerProp;
    }

    /**
     * Make human readable string for BYDAY
     *
     * Make the individual weekdays of BYDAY to a human readable string
     * Translation only in NL (Dutch)
     *
     * First try to find some day groups
     * When days are not in calenderal order, they will be returned as individual days
     * ('TU,MO,TH,FR,WE' => will NOT produce 'maandag tot vrijdag')
     *
     * Second go over the given day(s) to name each one of them
     * this can be one, or multiple:  'WE' / 'MO,TH,FR' / 'WE,FR'
     *
     * @param $byDay
     * @return string
     */
    public function hrByDay($byDay)
    {
        // handle BYDAY
        switch ($byDay) {
            case 'MO,TU,WE,TH':
                return 'maandag tot donderdag';
            case 'MO,TU,WE,TH,FR':
                return 'maandag tot vrijdag';
            case 'MO,TU,WE,TH,FR,SA':
                return 'maandag tot zaterdag';
            case 'SA,SU':
                return 'zaterdag en zondag';
            case 'MO,TU,WE,TH,FR,SA,SU':
                return 'dag van de week';
        }

        $usefullDays = [
            'MO' => 'maandag',
            'TU' => 'dinsdag',
            'WE' => 'woendag',
            'TH' => 'donderdag',
            'FR' => 'vrijdag',
            'SA' => 'zaterdag',
            'SU' => 'zondag',
        ];

        $transDays = [];
        $days = explode(',', $byDay);
        foreach ($days as $day) {
            if (isset($usefullDays[$day])) {
                $transDays[] = $usefullDays[$day];
            }
        }

        return implode(', ', $transDays);
    }

    /**
     * Make human readable string for BYSETPOS
     *
     * Nicked and altered from https://github.com/simshaun/recurr/blob/master/translations/nl.php
     * Made extra rule for 2nd last => "voorlaatste" in NL (Dutch)
     *
     * Rules for NL (Dutch) http://ans.ruhosting.nl/e-ans/07/03/01/body.html
     * state that only 8 and numbers >= 20 get suffix "ste"
     * other rules are applied for combination of number == 1 and ($number % 100) < 20 combinations
     * but this latter will not be of use in ical locig (so we take the 1 hardcoded in the exceptions)
     *
     * @param $number
     * @return string
     */
    public function hrBySetPos($number)
    {
        // special negative numbers
        switch ($number) {
            case -1:
                return 'laatste';
            case -2:
                return 'voorlaatste';
        }

        // regular negative numbers
        if ($number < 0) {
            return abs($number + 1) . ' na laatste';
        }

        if ($number == 1 || ($number % 10) == 8 || $number >= 20) {
            return $number . 'ste';
        }

        return $number . 'de';
    }
}
