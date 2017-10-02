<?php

namespace App\Formatters\Openinghours;

/**
 * HTML Formatter class for Openinghours
 * renders given objects into HTML
 */
class TextFormatter extends BaseFormatter
{
    /**
     * @var string
     */
    protected $supportFormat = 'text';
    /**
     * Create a readable text
     *
     * @param Illuminate\Database\Eloquent\Model $data
     * @return $this
     */
    public function render($data)
    {
        $text = '';

        foreach ($data as $channel => $info) {
            $text .= $channel . ': ' . PHP_EOL;
            $text .= $this->makeTextForDayInfo($info);
            $text .= PHP_EOL . PHP_EOL;
        }
        $this->output = rtrim($text, PHP_EOL);

        return $this;
    }

}
