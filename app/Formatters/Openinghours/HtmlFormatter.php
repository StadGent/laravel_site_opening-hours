<?php

namespace App\Formatters\Openinghours;

/**
 * HTML Formatter class for Openinghours
 * renders given objects into HTML
 */
class HtmlFormatter extends BaseFormatter
{
    /**
     * @var string
     */
    protected $supportFormat = 'html';
    /**
     * Render a schedule into HTML based on an array structure
     * @todo use (blade) template ???
     *
     * @param Illuminate\Database\Eloquent\Model $data
     * @return html
     */
    public function render($data)
    {
        $formattedSchedule = '<div>';

        foreach ($data as $channel => $schedule) {
            $formattedSchedule .= "<h4>$channel</h4>";
            if (!empty($schedule)) {
                if (is_array($schedule)) {
                    foreach ($schedule as $entry) {
                        $formattedSchedule .= "<div>$entry</div>";
                    }
                    continue;
                }
                $formattedSchedule .= "<div>$schedule</div>";
            }
        }
        $formattedSchedule .= '</div>';
        $this->output = $formattedSchedule;

        return $this;
    }

}
