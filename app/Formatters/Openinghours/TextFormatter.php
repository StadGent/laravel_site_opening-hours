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
        foreach ($data as $channelObj) {
            $text .= PHP_EOL . $channelObj->channel . ':' . PHP_EOL;
            for ($i = 0; $i < strlen($channelObj->channel) + 1; $i++) {
                $text .= '=';
            }
            $text .= PHP_EOL;
            if (isset($channelObj->openNow)) {
                $text .= $channelObj->openNow->label . PHP_EOL;
                continue;
            }
            $text .= $this->makeTextForDayInfo($channelObj->openinghours);
        }
        $this->output = $text;
        $this->output = rtrim($text, PHP_EOL);

        return $this;
    }
}
