<?php

namespace Tests\Formatters\Openinghours;

use App\Models\DayInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class JsonLdFormatterTest extends \TestCase
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

        $this->formatter = app('OHJsonLdFormatter');

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
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatJsonLdEhNotSureYet()
    {
        $this->formatter->service = $this->service;
        $this->formatter->render($this->data);
        $output = $this->formatter->getOutput();
        // No errors ... no problems
    }
}
