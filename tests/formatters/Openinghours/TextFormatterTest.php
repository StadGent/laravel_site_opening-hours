<?php

namespace Tests\Formatters\Openinghours;

use App\Models\DayInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TextFormatterTest extends \TestCase
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

        $this->formatter = app('OHTextFormatter');

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
    public function testFormatTextGivesAString()
    {
        $this->formatter->render($this->data);
        $output = $this->formatter->getOutput();
        $result = '';
        foreach ($this->service->channels as $channel) {
            $result .= $channel->label . ":";
            $result .= "15-09-2017:    van 09:00  tot 12:00   van 13:00  tot 17:00";
        }
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);

        $this->assertEquals($result, $cleanedoutput);
    }
}
