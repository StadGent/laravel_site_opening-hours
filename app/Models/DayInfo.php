<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Model to store Dayinfo from iCal
 */
class DayInfo
{
    /**
     * @var Carbon
     */
    public $date;

    /**
     * @var mixed
     */
    public $open = null;

    /**
     * @var array
     */
    public $hours = [];

    const WEEKDAYS_SHORT = [
        Carbon::SUNDAY => 'Su',
        Carbon::MONDAY => 'Mo',
        Carbon::TUESDAY => 'Tu',
        Carbon::WEDNESDAY => 'We',
        Carbon::THURSDAY => 'Th',
        Carbon::FRIDAY => 'Fr',
        Carbon::SATURDAY => 'Sa',
    ];

    const WEEKDAYS = [
        Carbon::SUNDAY => 'Sunday',
        Carbon::MONDAY => 'Monday',
        Carbon::TUESDAY => 'Tuesday',
        Carbon::WEDNESDAY => 'Wednesday',
        Carbon::THURSDAY => 'Thursday',
        Carbon::FRIDAY => 'Friday',
        Carbon::SATURDAY => 'Saturday',
    ];

    /**
     * @param \DateTime $date
     */
    public function __construct(\DateTime $date)
    {
        $this->date = $date instanceof Carbon ? $date : new Carbon($date);
    }

    /**
     * pretty print DayInfo Obj
     *
     * @return string
     */
    public function __toString()
    {
        $theString = '[' . $this->date->toDateString() . ' => [' . PHP_EOL .
            'open => ' . (int)$this->open . ', ' .
            'hours => [';
        foreach ($this->hours as $hours) {
            $theString .= PHP_EOL . '[from => ';
            if (isset($hours['from'])) {
                $theString .= $hours['from'];
            }
            $theString .= ', until => ';
            if (isset($hours['until'])) {
                $theString .= $hours['until'];
            }
            $theString .= '], ' . PHP_EOL;
        }

        if (isset($this->openNow)) {
            $theString .= 'openNow => [status => ' . $this->openNow->status .
                ', label' . $this->openNow->label . '], ' . PHP_EOL;
        }

        $theString .= ']]';

        return $theString;
    }
}
