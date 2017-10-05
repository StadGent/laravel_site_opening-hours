<?php

namespace App\Formatters\Openinghours;

use Illuminate\Database\Eloquent\Model;

/**
 * Json formatter class for Openinghours
 * renders given objects into json (base Laravel behavior)
 */
class JsonFormatter extends BaseFormatter
{
    /**
     * @var string
     */
    protected $supportFormat = 'json';
    /**
     * render
     * @param Illuminate\Database\Eloquent\Model $data
     * @return $this
     */
    public function render($data)
    {
        $this->output = $data;

        return $this;
    }
}
