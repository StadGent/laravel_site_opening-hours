<?php

namespace Tests;

use App\Models\Channel;
use App\Models\Service;

class OpeningHoursScenarioTest extends \TestCase
{
    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/services';

    /**
     * setup for each test
     */
    public function setup()
    {
        parent::setUp();
        $service = Service::first();
        $this->serviceId = $service->id;
        $this->channels = $service->channels;
    }

    /**
     * @test
     * @group content
     **/
    public function testOpenNowIsOpen()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/open-now?testDateTime=2017-09-05 10:00:00');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openNow' => ['status' => true, 'label' => 'open'],
                'channelTypeLabel' => $channel->type->name,
                'channelTypeId' => $channel->type->id
            ];
        }

        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpenNowIsClosed()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/open-now?testDateTime=2017-09-05 12:05:00');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openNow' => ['status' => false, 'label' => 'gesloten'],
                'channelType' => null,
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursFromUntil()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/openinghours?from=2017-09-03&until=2017-09-06');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openinghours' => [
                    ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                    ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                    [
                        'date' => '2017-09-05',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                    [
                        'date' => '2017-09-06',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                ],
                'channelTypeLabel' => $channel->type->name,
                'channelTypeId' => $channel->type->id
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursDayOnRegularDay()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/openinghours/day?date=2017-09-05');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openinghours' => [
                    [
                        'date' => '2017-09-05',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                ],
                'channelTypeLabel' => $channel->type->name,
                'channelTypeId' => $channel->type->id
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursDayOnExceptionCloseDay()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/openinghours/day?date=2017-09-04');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openinghours' => [
                    ['date' => '2017-09-04', 'open' => false, 'hours' => []]
                ],
                'channelType' => null,
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursWeek()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/openinghours/week?date=2017-09-05');
        $content = $this->getContentStructureTested();
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label,
                'channelId' => $channel->id,
                'openinghours' => [
                    ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                    [
                        'date' => '2017-09-05',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                    [
                        'date' => '2017-09-06',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                    [
                        'date' => '2017-09-07',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                    [
                        'date' => '2017-09-08',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']
                        ],
                    ],
                    [
                        'date' => '2017-09-09',
                        'open' => true,
                        'hours' => [
                            0 => ['from' => '10:00', 'until' => '12:00'],
                        ]
                    ],
                    ['date' => '2017-09-10', 'open' => false, 'hours' => []],
                ],
                'channelType' => null,
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursWeekFirstDayOfWeekByLocale()
    {
        $channel = Channel::find(1);
        $this->doRequest('GET', $this->apiUrl . '/1/channels/1/openinghours/week?date=2017-09-05', ['format' => 'text', 'lang' => 'nl']);
        $this->seeStatusCode(200);
        $output = $this->response->getContent();
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);
        $expected = $channel->label . ":" .
            "maandag 04/09: gesloten" .
            "dinsdag 05/09: 09:00-12:00 en 13:00-17:00" .
            "woensdag 06/09: 09:00-12:00 en 13:00-17:00" .
            "donderdag 07/09: 09:00-12:00 en 13:00-17:00" .
            "vrijdag 08/09: 09:00-12:00 en 13:00-17:00" .
            "zaterdag 09/09: 10:00-12:00" .
            "zondag 10/09: gesloten";
        $this->assertEquals($expected, $cleanedoutput);

        /* test on en-US => notice the week starts on Sunday */
        $this->doRequest('GET', $this->apiUrl . '/1/channels/1/openinghours/week?date=2017-09-05', ['format' => 'text', 'lang' => 'en-US']);
        $this->seeStatusCode(200);
        $output = $this->response->getContent();
        // remove all EOL's
        $removedEOL = str_replace(PHP_EOL, '', $output);
        // remove fancy double lines under channel names
        $cleanedoutput = str_replace('=', '', $removedEOL);

        $expected = $channel->label . ":" .
            "Sunday 09-03: closed" .
            "Monday 09-04: closed" .
            "Tuesday 09-05: 09:00 AM-12:00 PM and 01:00 PM-05:00 PM" .
            "Wednesday 09-06: 09:00 AM-12:00 PM and 01:00 PM-05:00 PM" .
            "Thursday 09-07: 09:00 AM-12:00 PM and 01:00 PM-05:00 PM" .
            "Friday 09-08: 09:00 AM-12:00 PM and 01:00 PM-05:00 PM" .
            "Saturday 09-09: 10:00 AM-12:00 PM";
        $this->assertEquals($expected, $cleanedoutput);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursMonth()
    {
        $this->doRequest('GET', $this->apiUrl . '/1/openinghours/month?date=2017-09-05');
        $this->getContentStructureTested();
    }

    /**
     * @param $content
     */
    protected function extraStructureTest($content)
    {
        $this->assertCount(count($this->channels), $content);
    }
}
