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

    /**
     * @param Carbon $date
     */
    public function __construct(Carbon $date)
    {
        $this->date = $date->toDateString();
    }

    /**
     * pretty print DayInfo Obj
     *
     * @return string
     */
    public function __toString()
    {
        $theString = '[' . $this->date . ' => [' . PHP_EOL .
        'open => ' . (int) $this->open . ', ' .
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
