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
        $this->channelKeys = $service->channels->pluck('id');
    }

    /**
     * @test
     * @group content
     **/
    public function testOpenNowIsOpen()
    {
        $call = $this->doRequest('/api/services/1/open-now?testDateTime=2017-09-05 10:00:00');
        $content = $this->getContentStructureTested($call);
        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openNow' => ['status' => true, 'label' => 'open']],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openNow' => ['status' => true, 'label' => 'open']],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openNow' => ['status' => true, 'label' => 'open']],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openNow' => ['status' => true, 'label' => 'open']],
        ];
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
        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openNow' => ['status' => false, 'label' => 'gesloten']],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openNow' => ['status' => false, 'label' => 'gesloten']],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openNow' => ['status' => false, 'label' => 'gesloten']],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openNow' => ['status' => false, 'label' => 'gesloten']],
        ];
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

        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openinghours' => [
                '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openinghours' => [
                '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openinghours' => [
                '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openinghours' => [
                '2017-09-03' => ['date' => '2017-09-03', 'open' => false, 'hours' => []],
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
        ];
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

        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openinghours' => [
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openinghours' => [
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openinghours' => [
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openinghours' => [
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
            ]],
        ];
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

        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
            ]],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
            ]],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
            ]],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
            ]],
        ];
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

        $expected = [
            0 => ['channel' => 'Tele-service', 'channelId' => 1, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
            ]],
            1 => ['channel' => 'Balie', 'channelId' => 2, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
            ]],
            2 => ['channel' => 'Technical staff', 'channelId' => 3, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
            ]],
            3 => ['channel' => 'Web-service', 'channelId' => 4, 'openinghours' => [
                '2017-09-04' => ['date' => '2017-09-04', 'open' => false, 'hours' => []],
                '2017-09-05' => ['date' => '2017-09-05', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-06' => ['date' => '2017-09-06', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-07' => ['date' => '2017-09-07', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-08' => ['date' => '2017-09-08', 'open' => true, 'hours' => [0 => ['from' => '09:00', 'until' => '12:00'], 1 => ['from' => '13:00', 'until' => '17:00']]],
                '2017-09-09' => ['date' => '2017-09-09', 'open' => false, 'hours' => []],
                '2017-09-10' => ['date' => '2017-09-10', 'open' => false, 'hours' => []],
            ]],
        ];
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
        $this->assertCount(count($this->channelKeys), $content);

        return $content;
    }

}
