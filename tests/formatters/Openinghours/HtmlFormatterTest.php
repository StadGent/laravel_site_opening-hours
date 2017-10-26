<?php

namespace Tests\Formatters\Openinghours;

use App\Models\DayInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HtmlFormatterTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var App\Formatters\Openinghours\HtmlFormatter
     */
    private $formatter;

    /**
     * @var array
     */
    private $data = [];

    public function setup()
    {
        parent::setup();

        $this->formatter = app('OHHtmlFormatter');

        $this->service = \App\Models\Service::first();
        foreach ($this->service->channels as $channel) {
            $channelObj = new \stdClass();
            $this->data[] = $channelObj;

            $channelObj->channel = $channel->label;
            $channelObj->channelId = $channel->id;
            $dayInfo = new DayInfo(new Carbon("2017-09-15"));
            $channelObj->openinghours["2017-09-15"] = $dayInfo;

            $dayInfo->open = true;
            $dayInfo->hours = [
                [
                    "from" => "09:00",
                    "until" => "12:00",
                ],
                [
                    "from" => "13:00",
                    "until" => "17:00",
                ],
            ];
        }
        $this->formatter->setDateTimeFormats('d-m-Y', 'H:i');
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatHtmlGivesHtml()
    {
        $this->formatter->render($this->data);
        $output = $this->formatter->getOutput();
        $result = '<div vocab="http://schema.org/" typeof="Library">';
        foreach ($this->service->channels as $channel) {
            $result .= '<h1>' . $channel->label . '</h1>' .
                '<div property="openingHoursSpecification" typeof="OpeningHoursSpecification">' .
                '<time property="validFrom validThrough" datetime="2017-09-15">15-09-2017</time>:  ' .
                'van <time property="opens" content="09:00:00">09:00</time> ' .
                'tot <time property="closes" content="12:00:00">12:00</time>  ' .
                'van <time property="opens" content="13:00:00">13:00</time> ' .
                'tot <time property="closes" content="17:00:00">17:00</time> </div>';
        }
        $result .= "</div>";
        $this->assertEquals($result, $output);
    }
}
