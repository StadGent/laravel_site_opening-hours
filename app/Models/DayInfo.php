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
     * @return string
     */
    public function __toString()
    {
        $theString = '[' . $this->date . ' => [' .
        'open => ' . (int) $this->open . ', ' .
            'hours => [';
        foreach ($this->hours as $hours) {
            $theString .= '[from => ' . $hours->from . ', until' . $hours->until . '], ';
        }
        if (isset($this->openNow)) {
            $theString .= 'openNow => [status => ' . $this->openNow->status . ', label' . $this->openNow->label . '], ';
        }

        $theString .= ']]';

        return $theString;
    }
}
