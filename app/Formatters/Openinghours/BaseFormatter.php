<?php

namespace App\Formatters\Openinghours;

use App\Formatters\FormatterInterface;
use App\Models\Openinghours;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 *
 */
abstract class BaseFormatter implements FormatterInterface
{
    /**
     * @var mixed
     */
    protected $output = null;

    /**
     * @var string
     */
    protected $supportFormat = null;

    /**
     * @var string
     */
    protected $dateFormat = '';

    /**
     * @var string
     */
    protected $timeFormat = '';

    /**
     *
     * @param Illuminate\Database\Eloquent\Model $data
     */
    abstract public function render($data);

    /**
     * Be able to manipulate the formats without an actual http request
     *
     * initial purpose is to be set for unit tests
     *
     * @param string $dateFormat
     * @param string $timeFormat
     */
    public function setDateTimeFormats($dateFormat, $timeFormat)
    {
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
    }

    /**
     * Getter of supportFormat
     *
     * @return string
     */
    public function getSupportFormat()
    {
        if (!$this->supportFormat) {
            throw new \Exception("Error supportFormat not declared in " . get_class($this), 1);
        }

        return $this->supportFormat;
    }

    /**
     * Print a textual representation of a day schedule
     *
     * @param  array $openinghours
     * @return string
     */
    protected function makeTextForDayInfo($openinghours)
    {
        $text = '';
        foreach ($openinghours as $ohObj) {
            $text .= trans('openinghourApi.day_' . date('w', strtotime($ohObj->date))) . ' ';
            $text .= date($this->dateFormat, strtotime($ohObj->date)) . ': ';
            if (!$ohObj->open) {
                $text .= trans('openinghourApi.CLOSED');
                $text .= PHP_EOL;
                continue;
            }
            $hours = [];
            foreach ($ohObj->hours as $hoursObj) {
                $hours[] = date(
                    $this->timeFormat,
                    strtotime($hoursObj['from'])
                ) . "-" .
                date($this->timeFormat, strtotime($hoursObj['until']));
            }

            // implode hours[] with ', ' but make last ', '  =>  "and"
            // to result in for example 'HH:ii-HH:ii, HH:ii-HH:ii, HH:ii-HH:ii and HH:ii-HH:ii'
            // https://stackoverflow.com/a/8586179
            $last = array_slice($hours, -1);
            $first = implode(', ', array_slice($hours, 0, -1));
            $both = array_filter(array_merge([$first], $last), 'strlen');
            $text .= implode(' ' . trans('openinghourApi.AND') . ' ', $both);

            $text .= PHP_EOL;
        }
        $text .= PHP_EOL;

        return $text;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }
}
