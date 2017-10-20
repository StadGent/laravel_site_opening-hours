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
        $this->formatter->render('json', []);
    }

    /**
     * @test
     * @group validation
     */
    public function testRequestUnknownFormatThrowsError()
    {
        $this->setExpectedException(
            'Exception',
            'The given format NotAFormatter is not available in App\Formatters\OpeninghoursFormatter'
        );
        $this->formatter->render('NotAFormatter', ['thisIsData' => true]);
    }

    /**
     * @test
     * @group content
     */
    public function testFormatJsonJustReturnsOriginalData()
    {
        $output = $this->formatter->render('json', $this->data);
        $this->assertEquals(array_values($this->data), $output);
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatJsonLdEhNotSureYet()
    {
        $this->formatter->setService($this->service);
        $this->formatter->render('json-ld', $this->data);
        // No errors ... no problems
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatHtmlGivesHtml()
    {
        $output = $this->formatter->render('html', $this->data);
        $result = "<div>";
        foreach ($this->service->channels as $channel) {
            $result .= "<h4>" . $channel->label . "</h4><div>15-09-2017</div><ul><li>09:00 - 12:00</li>" .
                "<li>13:00 - 17:00</li></ul>";
        }
        $result .= "</div>";
        $this->assertEquals($result, $output);
    }

    /**
     * @test
     * @group content
     * @todo check content when structure is known
     */
    public function testFormatTextGivesAString()
    {
        $output = $this->formatter->render('text', $this->data);
        $result = '';
        foreach ($this->service->channels as $channel) {
            $result .= $channel->label . ":";
            $result .= "15-09-2017:    09:00 - 12:00   13:00 - 17:00";
        }
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);

        $this->assertEquals($result, $cleanedoutput);
    }
}
