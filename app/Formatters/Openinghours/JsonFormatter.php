<?php

namespace App\Formatters\Openinghours;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * Json formatter class for Openinghours
 * renders given objects into json (base Laravel behavior)
 */
class JsonFormatter extends BaseFormatter
{
    /**
     * @var string
     */
    protected $supportFormat = 'application/json';
    /**
     * render
     * @param Illuminate\Database\Eloquent\Model $data
     * @return $this
     */
    public function render($data)
    {
        $this->output = array_values($data);
        if (count($this->output) === 1 && !($this->output instanceof Collection)) {
            $this->output = json_encode($this->output[0]);
        }

        return $this;
    }
}
