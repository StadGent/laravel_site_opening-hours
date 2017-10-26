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
    protected $supportFormat = 'text/html';
    /**
     * Render a schedule into HTML based on an array structure
     * @todo use (blade) template ???
     *
     * @param Illuminate\Database\Eloquent\Model $data
     * @return $this
     */
    public function render($data)
    {
        $formattedSchedule = '<div>';

        foreach ($data as $channelObj) {
            $formattedSchedule .= "<h4>$channelObj->channel</h4>";
            if (isset($channelObj->openNow)) {
                $formattedSchedule .= "<div>" . $channelObj->openNow->label . "</div>";
                continue;
            }

            foreach ($channelObj->openinghours as $ohObj) {
                $formattedSchedule .= "<div>" . date($this->dateFormat, strtotime($ohObj->date)) . "</div>";
                $formattedSchedule .= "<ul>";

                if (!$ohObj->open) {
                    $formattedSchedule .= trans('openinghourApi.CLOSED');
                    $formattedSchedule .= "</ul>";
                    continue;
                }

                foreach ($ohObj->hours as $hoursObj) {
                    $formattedSchedule .= "<li>" . date($this->timeFormat, strtotime($hoursObj['from'])) . " - " .
                    date($this->timeFormat, strtotime($hoursObj['until'])) . "</li>";
                }
                $formattedSchedule .= "</ul>";
            }
        }
        $formattedSchedule .= '</div>';
        $this->output = $formattedSchedule;

        return $this;
    }
}
