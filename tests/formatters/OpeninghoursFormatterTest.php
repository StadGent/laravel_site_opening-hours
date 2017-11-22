<?php

namespace Tests\Formatters;

use App\Models\DayInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursFormatterTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var App\Formatters\Openinghours
     */
    private $formatter;

    /**
     * @var array
     */
    private $data = [];

    public function setup()
    {
        parent::setup();

        $this->formatter = app('OpeninghoursFormatter');

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
     * @group validation
     */
    public function testAddUnknownFormatThrowsError()
    {
        $this->setExpectedException(
            'Exception',
            'NotAFormatter is not supported as format for App\Formatters\OpeninghoursFormatter'
        );
        $this->formatter->addFormat('NotAFormatter');
    }

    /**
     * @test
     * @group validation
     */
    public function testNoDataThrowsError()
    {
        $this->setExpectedException(
            'Exception',
            'No data given for formatterApp\Formatters\OpeninghoursFormatter'
        );
        $this->formatter->render([]);
    }

    /**
     * @test
     * @group validation
     */
    public function testRequestUnknownFormatThrowsError()
    {
        $this->setExpectedException(
            'Exception',
            'Error Processing Request as in absence of a request'
        );
        $this->formatter->render(['thisIsData' => true]);
    }
}
