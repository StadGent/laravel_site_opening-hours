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
     * @var App\Services\LocaleService
     */
    private $localeService;

    /**
     * @var array
     */
    private $data = [];

    public function setup()
    {
        parent::setup();

        $this->formatter = app('OHTextFormatter');
        $this->localeService = app('LocaleService');

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
    public function testFormatTextGivesAString()
    {
        $this->localeService->setLocale('nl-BE');
        $this->formatter->setDateTimeFormats($this->localeService->getDateFormat(), $this->localeService->getTimeFormat());

        $this->formatter->render($this->data);
        $output = $this->formatter->getOutput();
        $expect = '';
        foreach ($this->service->channels as $channel) {
            $expect .= $channel->label . ":";
            $expect .= "vrijdag 15/09: 09:00-12:00 en 13:00-17:00";
        }
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);

        $this->assertEquals($expect, $cleanedoutput);
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatTextGivesAStringInEnUsFormat()
    {
        $this->localeService->setLocale('en-US');
        $this->formatter->setDateTimeFormats($this->localeService->getDateFormat(), $this->localeService->getTimeFormat());

        $this->formatter->render($this->data);
        $output = $this->formatter->getOutput();
        $expect = '';
        foreach ($this->service->channels as $channel) {
            $expect .= $channel->label . ":";
            $expect .= "Friday 09-15: 09:00 AM-12:00 PM and 01:00 PM-05:00 PM";
        }
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);

        $this->assertEquals($expect, $cleanedoutput);
    }
}
