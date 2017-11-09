<?php

namespace Tests;

class OpeningHoursScenarioTest extends \TestCase
{
    /**
     * setup for each test
     */
    public function setup()
    {
        parent::setUp();
        $service = \App\Models\Service::first();
        $this->serviceId = $service->id;
        $this->channels = $service->channels;
    }

    /**
     * @test
     * @group content
     **/
    public function testOpenNowIsOpen()
    {
        $this->doRequest('GET', '/api/v1/services/1/open-now?testDateTime=2017-09-05 10:00:00');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = ['channel' => $channel->label, 'channelId' => $channel->id,
                'openNow' => ['status' => true, 'label' => 'open'],
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
        $this->doRequest('GET', '/api/v1/services/1/open-now?testDateTime=2017-09-05 12:05:00');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = ['channel' => $channel->label, 'channelId' => $channel->id,
                'openNow' => ['status' => false, 'label' => 'gesloten'],
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
        $this->doRequest('GET', '/api/v1/services/1/openinghours?from=2017-09-03&until=2017-09-06');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                    '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                    '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                    '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                    '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                ]];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursDayOnRegularDay()
    {
        $this->doRequest('GET', '/api/v1/services/1/openinghours/day?date=2017-09-05');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                    '2017-09-05' => ['date' => '2017-09-05', 'open' => true,
                        'hours' => [
                            0 => ['from' => '09:00', 'until' => '12:00'],
                            1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                ],
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
        $this->doRequest('GET', '/api/v1/services/1/openinghours/day?date=2017-09-04');
        $content = $this->getContentStructureTested();
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                    '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []]],
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
        $this->doRequest('GET', '/api/v1/services/1/openinghours/week?date=2017-09-05');
        $content = $this->getContentStructureTested();
        foreach ($this->channels as $channel) {
            $expected[] = [
                'channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                    '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                    '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'],
                        1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                    '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'],
                        1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                    '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'],
                        1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                    '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [
                        0 => ['from' => '09:00', 'until' => '12:00'],
                        1 => ['from' => '13:00', 'until' => '17:00']],
                    ],
                    '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                    '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
                ],
            ];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursMonth()
    {
        $this->doRequest('GET', '/api/v1/services/1/openinghours/month?date=2017-09-05');
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
