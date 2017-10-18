<?php

namespace App\Formatters\Openinghours;

use App\Formatters\FormatterInterface;
use App\Models\Openinghours;
use Illuminate\Database\Eloquent\Model;

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
     *
     * @param Illuminate\Database\Eloquent\Model $data
     */
    abstract public function render($data);

    /**
     * Getter of supportFormat
     * @return string $this->supportFormat
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
     * @param  string|array $dayInfo
     * @return string
     */
    protected function makeTextForDayInfo($openinghours)
    {
        $text = '';
        foreach ($openinghours as $ohObj) {
            $text .= date('d-m-Y', strtotime($ohObj->date)) . ': ';
            if ($ohObj->open) {
                foreach ($ohObj->hours as $hoursObj) {
                    $text .= '   ' . $hoursObj['from'] . " - " . $hoursObj['until'];
                }
            } else {
                $text .= '   ' . trans('openinghourApi.CLOSED');
            }

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
