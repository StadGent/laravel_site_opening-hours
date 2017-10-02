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
    protected function makeTextForDayInfo($dayInfo)
    {

        if (is_array($dayInfo)) {
            $text = '';
            foreach ($dayInfo as $date => $oh) {
                $text .= date('d-m-Y', strtotime($date)) . ' ' . $oh . PHP_EOL;
            }

            return $text;
        }

        return $dayInfo . PHP_EOL;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }
}
