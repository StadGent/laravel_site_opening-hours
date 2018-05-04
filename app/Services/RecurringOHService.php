<?php

namespace App\Services;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Event;
use App\Models\Openinghours;
use App\Models\Service;
use Carbon\Carbon;

class RecurringOHService
{

    private static $instance;

    const YEARLY = 'YEARLY';
    const MONTHLY = 'MONTHLY';
    const WEEKLY = 'WEEKLY';
    const DAILY = 'DAILY';

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getServiceOutput(Service $service, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        foreach ($service->channels as $channel) {
            $channelOutput = $this->getChannelOutput($channel, $startDate, $endDate);
            if ($channelOutput) {
                $output .= '<h4>' . ucfirst($channel->label) . '</h4>' . PHP_EOL;
                $output .= $channelOutput;
            }
        }

        return $output;
    }

    public function getChannelOutput(Channel $channel, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        $openinghoursCollection = $channel->openinghours()
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        foreach ($openinghoursCollection as $openinghours) {
            $output .= $this->getOpeninghoursOutput($openinghours, $startDate, $endDate);
        }

        return $output;
    }

    public function getOpeninghoursOutput(Openinghours $openinghours, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        $calendarCollection = $openinghours->calendars()->orderBy('priority', 'desc')->get();
        foreach ($calendarCollection as $calendar) {
            $output .= $this->getCalendarOutput($calendar, $startDate, $endDate);
        }

        return $output;
    }

    public function getCalendarOutput(Calendar $calendar, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        // TODO : the code below should be refactored, this is a quick and ugly workaround
        // The frequency, hours and availability are made human readable but this is done line by line
        // If you want to combine 2 lines (for example 08:00-09:00 and 10:00-12:00) this should be put
        // in a matrix to combine these before they are made human readable

        $rulesMatrix = [];

        $byDayIndexes = [
            'MO' => 0,
            'TU' => 1,
            'WE' => 2,
            'TH' => 3,
            'FR' => 4,
            'SA' => 5,
            'SU' => 6,
            '_' => 7,
        ];

        foreach ($calendar->events as $event) {
            $isValid = $this->validateEvent($event, $startDate, $endDate);

            if ($isValid) {
                $period = $this->getHumandReadableFrequency($event, $startDate, $endDate);
                $hours = $this->getHumanReadableHours($event);
                if (!$hours) {
                    $hours = 'gesloten';
                }

                $availability = $this->getHumanReadableAvailabillity($event, $startDate, $endDate);

                if ($event->calendar->priority != 0) {
                    $key = (new Carbon($event->start_date))->getTimestamp();
                } else {
                    $properties = $this->getRuleProperties($event->rrule);
                    $byDayArr = explode(",", $properties['BYDAY'], 2);
                    $byDayIndex = $byDayIndexes[$byDayArr[0]];
                    $key = $byDayIndex . (new Carbon($event->start_date))->format('His');
                }

                $rulesMatrix[] = [
                    'key' => $key,
                    'period' => $period,
                    'hours' => $hours,
                    'availability' => $availability,
                ];
            }
        }

        usort($rulesMatrix, function ($a, $b) {
            return $a['key'] - $b['key'];
        });

        $lastPeriod = null;
        $lastAvailability = null;

        foreach ($rulesMatrix as $index => $rulesArray) {
            $currentPeriod = $rulesArray['period'];
            $currentAvailability = $rulesArray['availability'];

            if ($currentPeriod == $lastPeriod && $lastAvailability == $currentAvailability) {
                $rulesMatrix[$index - 1]['hours'] = $rulesMatrix[$index - 1]['hours'] . ' en ' . $rulesArray['hours'];
                unset($rulesMatrix[$index]);
                continue;
            }

            $lastPeriod = $currentPeriod;
            $lastAvailability = $currentAvailability;
        }

        $rules = [];

        foreach ($rulesMatrix as $ruleArray) {
            $rule = $ruleArray['period'] . ': ' . $ruleArray['hours'];
            if ($ruleArray['availability'] != '') {
                $rule .= ',' . $ruleArray['availability'];
            }

            $rules[] = $rule;
        }

        if (!empty($rules)) {
            $output .= '<div>' . PHP_EOL;
            if ($calendar->priority != 0) {
                $output .= '<h4>';
                $output .= ucfirst($calendar->label);
                $output .= '</h4>' . PHP_EOL;
            }

            $output .= '<p>' . implode("<br />" . PHP_EOL, $rules) . '</p>' . PHP_EOL;
            $output .= '</div>' . PHP_EOL;
        }

        return $output;
    }

    private function getHumandReadableFrequency(Event $event, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        $eventStart = new Carbon($event->start_date);
        $eventEnd = new Carbon($event->end_date);
        $eventUntill = new Carbon($event->until);

        $frequency = $this->getFrequency($event);
        $properties = $this->getRuleProperties($event->rrule);

        if ($frequency == self::YEARLY) {
            if ($eventStart->format('Y-m-d') != $eventEnd->format('Y-m-d')) {
                $output .= $this->getFullDayOutput($eventStart) . ' - ' . $this->getFullDayOutput($eventEnd);
            }

            if ($eventStart->format('Y-m-d') == $eventEnd->format('Y-m-d')) {
                $output .= $this->getFullDayOutput($eventStart);
            }
        }

        if ($frequency == self::WEEKLY) {

            if (isset($properties['INTERVAL'])) {
                $output .= $properties['INTERVAL'] . '-wekelijks ';
            }

            if (isset($properties['BYSETPOS'])) {
                $output .= $this->getHumanReadableFormatForNumber($properties['BYSETPOS']) . ' ';
            }

            if (isset($properties['BYDAY'])) {
                $output .= $this->getHumanReadableFormatForDay($properties['BYDAY']);
            }

            if (isset($rrulePerProp['BYMONTHDAY'])) {
                $output .= $this->getHumanReadableFormatForNumber($properties['BYMONTHDAY']);
            }
        }

        if ($frequency == self::MONTHLY) {
            $output .= 'Elke ';

            if (isset($properties['BYSETPOS'])) {
                $output .= $this->getHumanReadableFormatForNumber($properties['BYSETPOS']) . ' ';
            }

            if (isset($properties['BYDAY'])) {
                $output .= $this->getHumanReadableFormatForDay($properties['BYDAY']);
            }

            if (isset($rrulePerProp['BYMONTHDAY'])) {
                $output .= $this->getHumanReadableFormatForNumber($properties['BYMONTHDAY']);
            }

            $output .= ' van de maand';
        }

        if ($frequency == self::DAILY || $frequency == '') {
            if ($eventStart->format('Y-m-d') == $eventUntill->format('Y-m-d')) {
                $output .= $this->getFullDayOutput($eventStart);
            } else {
                $output .= $this->getFullDayOutput($eventStart) . ' tot en met ' . $this->getFullDayOutput($eventUntill);
            }
        }

        return $output;
    }

    /**
     * Split the RRule up into props
     *
     * @param $rrime
     * @return mixed
     */
    public function getRuleProperties($rrule)
    {
        $properties = [];
        $propertyStrings = explode(';', $rrule);

        foreach ($propertyStrings as $propertyString) {
            $propertyStringParts = explode('=', $propertyString);
            $propertyKey = $propertyStringParts[0];
            $propertyValue = $propertyStringParts[1];
            $properties[$propertyKey] = $propertyValue;
        }

        return $properties;
    }


    /**
     * @param Event $event
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return bool
     *
     * Eliminate events not within the periode of next 3 months
     */
    public function validateEvent(Event $event, Carbon $startDate, Carbon $endDate)
    {
        if (!$event->calendar) {
            return false;
        }

        $eventStartHour = new Carbon($event->start_date);
        $eventEndHour = new Carbon($event->end_date);
        $eventUntilDate = new Carbon($event->until);

        // check event until > begin of periode to skip
        if ($eventUntilDate->lessThan($startDate)) {
            return false;
        }

        // check event start is later then end of periode to skip
        if ($eventStartHour->greaterThan($endDate)) {
            return false;
        }

        if (strpos($event->rrule, 'FREQ=YEARLY') !== false) {
            $difference = $eventEndHour->year - $eventStartHour->year;

            // take the year of the startDate
            $eventStartHour->year = $startDate->year;
            $eventEndHour->year = $startDate->year;

            $eventEndHour->addYears($difference);

            // the end could be in the next year for example Christmas Holidays
            if ($eventEndHour->greaterThan($startDate) && $eventStartHour->lessThan($endDate)) {
                return true;
            }

            // check on year leap (needed for when request if from December until February you have a 2nd new year to check)
            if ($startDate->year != $endDate->year) {
                $eventStartHour->year = $endDate->year;
                $eventEndHour->year = $endDate->year;

                // the end could be in the next year
                $eventEndHour->addYears($difference);
                if ($eventEndHour->greaterThan($startDate) && $eventStartHour->lessThan($endDate)) {
                    return true;
                }

                return false;
            }

            return false;
        }

        return true;
    }

    /**
     * @param $number
     * @return string
     */
    private function getHumanReadableFormatForNumber($number)
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

    public function getHumanReadableFormatForDay($byDay)
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
                return 'elke dag van de week';
        }

        $usefullDays = [
            'MO' => 'maandag',
            'TU' => 'dinsdag',
            'WE' => 'woensdag',
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

    private function getFullDayOutput(Carbon $event)
    {
        $translatedDay = trans('openinghourApi.' . $event->format('l'), [], 'messages', 'nl');
        $translatedMonth = trans('openinghourApi.' . $event->format('F'), [], 'messages', 'nl');

        $output = strtolower($translatedDay);
        $output .= ' ';
        $output .= $event->format('j');
        $output .= ' ';
        $output .= strtolower($translatedMonth);
        $output .= ' ';
        $output .= $event->format('Y');


        return $output;
    }

    private function getHumanReadableHours(Event $event)
    {
        $hours = $event->calendar->closinghours;

        if ($hours) {
            return false;
        }

        $eventStart = new Carbon($event->start_date);
        $eventEnd = new Carbon($event->end_date);

        $output = 'van ';
        $output .= $this->getFullTimeOutput($eventStart);
        $output .= ' tot ';
        $output .= $this->getFullTimeOutput($eventEnd);
        $output .= ' uur';

        return $output;
    }

    private function getFullTimeOutput(Carbon $date)
    {
        $hour = $date->format('G');
        $minutes = $date->format('i');

        $output = $hour;
        if ($minutes != '00') {
            $output .= '.' . $minutes;
        }

        return $output;
    }

    private function getFrequency(Event $event)
    {
        $properties = $this->getRuleProperties($event->rrule);
        return $properties['FREQ'];
    }

    private function getHumanReadableAvailabillity(Event $event, Carbon $startDate, Carbon $endDate)
    {
        $output = '';

        $frequency = $this->getFrequency($event);

        if (
            $frequency == self::MONTHLY ||
            $frequency == self::WEEKLY
        ) {
            $eventStart = (new Carbon($event->start_date))->startOfDay();
            $eventUntil = (new Carbon($event->until))->startOfDay();

            if ($event->calendar->priority == 0) {
                $eventUntil = (new Carbon($event->calendar->openinghours->end_date));
            }

            if ($eventStart->greaterThan($startDate)) {
                $output .= ' geldig vanaf ' . $this->getFullDayOutput($eventStart);
            }
            if ($eventUntil->lessThan($endDate)) {
                if ($output == '') {
                    $output .= ' geldig';
                }
                $output .= ' t.e.m. ' . $this->getFullDayOutput($eventUntil);
            }
        }

        return $output;
    }
}
