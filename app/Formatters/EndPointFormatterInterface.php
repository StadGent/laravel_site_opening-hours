<?php

namespace App\Formatters;

/**
 * Formatter class
 * renders given data into given format
 */
interface EndPointFormatterInterface
{
    /**
     * Render data according to the given format
     *
     * @param  string $format
     * @param  array $data   data to transform
     */
    public function render($data);

    /**
     *
     * @param $formatter
     */
    public function addFormat($formatter);
}
