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

        foreach ($data as $channelObj) {
            $formattedSchedule .= "<h4>$channelObj->channel</h4>";
            if (isset($channelObj->openNow)) {
                $formattedSchedule .= "<div>" . $channelObj->openNow->label . "</div>";
            } else {

                foreach ($channelObj->openinghours as $ohObj) {
                    $formattedSchedule .= "<div>" . date('d-m-Y', strtotime($ohObj->date)) . "</div>";
                    $formattedSchedule .= "<ul>";

                    if ($ohObj->open) {
                        foreach ($ohObj->hours as $hoursObj) {
                            $formattedSchedule .= "<li>" . $hoursObj['from'] . " - " . $hoursObj['until'] . "</li>";
                        }
                    } else {
                        $formattedSchedule .= trans('openinghourApi.CLOSED');
                    }

                    $formattedSchedule .= "</ul>";
                }
            }

        }
        $formattedSchedule .= '</div>';
        $this->output = $formattedSchedule;

        return $this;
    }

}
