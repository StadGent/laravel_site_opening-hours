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
        $call = $this->doRequest('/api/services/1/open-now?testDateTime=2017-09-05 10:00:00');
        $content = $this->getContentStructureTested($call);
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openNow' => ['status' => true, 'label' => 'open'],
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
        $call = $this->doRequest('/api/services/1/open-now?testDateTime=2017-09-05 12:05:00');
        $content = $this->getContentStructureTested($call);
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openNow' => ['status' => false, 'label' => 'gesloten'],
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
        $call = $this->doRequest('/api/services/1/openinghours?from=2017-09-03&until=2017-09-06');
        $content = $this->getContentStructureTested($call);
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
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
        $call = $this->doRequest('/api/services/1/openinghours/day?date=2017-09-05');
        $content = $this->getContentStructureTested($call);
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursDayOnExceptionCloseDay()
    {
        $call = $this->doRequest('/api/services/1/openinghours/day?date=2017-09-04');
        $content = $this->getContentStructureTested($call);
        $expected = [];
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
            ]];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursWeek()
    {
        $call = $this->doRequest('/api/services/1/openinghours/week?date=2017-09-05');
        $content = $this->getContentStructureTested($call);
        foreach ($this->channels as $channel) {
            $expected[] =
                ['channel' => $channel->label, 'channelId' => $channel->id, 'openinghours' => ['2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
            ]];
        }
        $this->assertEquals($expected, $content);
    }

    /**
     * @test
     * @group content
     **/
    public function testOpeninghoursMonth()
    {
        $call = $this->doRequest('/api/services/1/openinghours/month?date=2017-09-05');
        $content = $this->getContentStructureTested($call);

    }

    /**
     * do request according to the given format
     */
    public function doRequest($path)
    {
        return $this->json(
            'GET',
            $path,
            [],
            [
                'Accept-Encoding' => 'gzip, deflate',
                'Accept-Language' => 'nl-NL,nl;q=0.8,en-US;q=0.6,en;q=0.4',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
    }

    /**
     * get contect from call
     * and do base tests
     */
    public function getContentStructureTested($call)
    {
        // check status code
        $call->seeStatusCode(200);
        $content = $call->decodeResponseJson();
        $this->assertCount(count($this->channels), $content);

        return $content;
    }

}
