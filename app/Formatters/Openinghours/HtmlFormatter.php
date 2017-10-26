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
        $formattedSchedule = '<div vocab="http://schema.org/" typeof="Library">';

        foreach ($data as $channelObj) {
            $formattedSchedule .= "<h1>$channelObj->channel</h1>";
            if (isset($channelObj->openNow)) {
                $formattedSchedule .= "<div>" . $channelObj->openNow->label . "</div>";
                continue;
            }

            foreach ($channelObj->openinghours as $ohObj) {
                $formattedSchedule .= '<div property="openingHoursSpecification" typeof="OpeningHoursSpecification">' .
                '<time property="validFrom validThrough" datetime="' . date('Y-m-d', strtotime($ohObj->date)) . '">' .
                date($this->dateFormat, strtotime($ohObj->date)) . '</time>: ';
                if (!$ohObj->open) {
                    $formattedSchedule .= '<time property="closes" datetime="' .
                    date('Y-m-d', strtotime($ohObj->date)) . '">' .
                    trans('openinghourApi.CLOSED') .
                        '</time></div>';
                    continue;
                }

                foreach ($ohObj->hours as $hoursObj) {
                    $formattedSchedule .= ' ' . trans('openinghourApi.FROM_HOUR') . ' ' .
                    '<time property="opens" content="' . date('H:i:s', strtotime($hoursObj['from'])) . '">' .
                    date($this->timeFormat, strtotime($hoursObj['from'])) .
                    '</time> ' .
                    trans('openinghourApi.UNTIL_HOUR') . ' ' .
                    '<time property="closes" content="' . date('H:i:s', strtotime($hoursObj['until'])) . '">' .
                    date($this->timeFormat, strtotime($hoursObj['until'])) .
                        '</time> ';
                }
                $formattedSchedule .= "</div>";
            }
        }
        $formattedSchedule .= '</div>';
        $this->output = $formattedSchedule;

        return $this;
    }
}
