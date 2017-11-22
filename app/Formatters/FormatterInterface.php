<?php

namespace App\Formatters;

interface FormatterInterface
{
    /**
     * Render data according to the given format
     *
     * @param  array $data
     */
    public function render($data);

    /**
     * Getter of supportFormat
     */
    public function getSupportFormat();
}
