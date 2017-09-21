<?php

namespace Tests\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class QueryControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * [$service description]
     * @var \App\Models\Service
     */
    protected $service;
    /**
     * store the optional channel labels
     * @var array
     */
    protected $channelKeys = [];

    /**
     * store the optional format key
     * allowed: [null, 'html', 'json-ls', 'json']
     * @var string
     */
    protected $format = null;

    /**
     * setup for each test
     */
    public function setup()
    {
        parent::setUp();
        $this->service = \App\Models\Service::first();

        $this->channelKeys = $this->service->channels()->pluck('label')->all();
    }

    public function requestTypeProvider()
    {
        $dateParam = date('d-m-Y', strtotime('tomorrow'));
        return [
            [['q' => 'now']],
            [['q' => 'day', 'date' => $dateParam]],
            [['q' => 'week']],
            [['q' => 'fullWeek', 'date' => $dateParam]],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestTypeProvider
     **/
    public function testValidateNoServiceUriParameterIsAFail($typeParams)
    {
        // undo service setter
        // will be restored for next test by setup
        $this->service = null;
        $call          = $this->doRequest('GET', $typeParams);
        $call->seeStatusCode(400);
        $call->seeJson([
            "serviceUri" => ["The service uri field is required."],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateWrongServiceUriParameterIsAFail()
    {
        // undo service setter
        // will be restored for next test by setup
        $this->service = null;
        $call          = $this->doRequest(
            'GET',
            ['q' => 'now', 'serviceUri' => 'thisIsNotAServiceUri', 'date' => date('d-m-Y')]
        );
        $call->seeStatusCode(400);

        $call->seeJson([
            "serviceUri" => ["The selected service uri is invalid."],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateServiceWithoutChannelsReturnsNotFoundError()
    {
        $this->service = factory(\App\Models\Service::class)->create();
        $call          = $this->doRequest('GET', ['q' => 'now', 'date' => date('d-m-Y')]);
        $call->seeStatusCode(400);

        $call->seeJson([
            "serviceUri" => ["The selected service uri is not available yet."],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateNoQParameterIsAFail()
    {
        // {host}/api/query?serviceUri={serviceUri}&format={format}
        $call = $this->doRequest('GET', []);
        $call->seeStatusCode(400);
        $call->seeJson([
            "q" => ["The q field is required."],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateWrongQParameterIsAFail()
    {
        // {host}/api/query?q=norealq&serviceUri={serviceUri}&format={format}
        $call = $this->doRequest('GET', ['q' => 'noreal_q']);
        $call->seeStatusCode(400);

        $call->seeJson([
            'q' => ['The selected parameter q is invalid.'],
        ]);
    }

    /**
     * @test
     * @group content
     * @dataProvider requestTypeProvider
     **/
    public function testItHasOnlyOneChannelkeyWhenChannelParamIsGiven($typeParams)
    {
        // {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}&format={format}
        $this->channelKeys = array_shift($this->channelKeys);
        $call              = $this->doRequest('GET', $typeParams);
        $content           = $this->getContentStructureTested($call);
        $this->assertCount(1, $content);
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeNow()
    {
        // {host}/api/query?q=now&serviceUri={serviceUri}&format={format}
        $call = $this->doRequest('GET', ['q' => 'now']);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeDayWithDateParam()
    {
        // {host}/api/query?q=day&date={mm-dd-yyyy}&serviceUri={serviceUri}&format={format}
        $call = $this->doRequest('GET', ['q' => 'day', 'date' => date('d-m-Y')]);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysOrNullPerChannelOnTypeWeek()
    {
        // {host}/api/query?q=week&serviceUri={serviceUri}&format={format}
        $call    = $this->doRequest('GET', ['q' => 'week']);
        $content = $this->getContentStructureTested($call);
        $this->checkSevenDaysOrNullPerChannel($content);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysOrNullPerChannelOnTypeFullWeek()
    {
        // {host}/api/query?q=fullWeek&serviceUri={serviceUri}&format={format}

        $dateParam = date('d-m-Y', strtotime('tomorrow'));
        $call      = $this->doRequest('GET', ['q' => 'fullWeek', 'date' => $dateParam]);
        $content   = $this->getContentStructureTested($call);
        $this->checkSevenDaysOrNullPerChannel($content);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysOrNullPerChannelOnTypeFullWeekWithDateParam()
    {
        // {host}/api/query?q=fullWeek&serviceUri={serviceUri}&date=dd-mm-yyyy&format={format}
        $call    = $this->doRequest('GET', ['q' => 'fullWeek', 'date' => date('d-m-Y')]);
        $content = $this->getContentStructureTested($call);
        $this->checkSevenDaysOrNullPerChannel($content);
    }

    /**
     * returns true if we have a value in the channel parameter
     * @return boolean
     */
    private function oneChannel()
    {
        return count($this->channelKeys) === 1;
    }

    /**
     * do request according to the given format
     */
    protected function doRequest($type, $params = null)
    {
        if (is_array($params)) {
            $params = $this->assembleParameters($params);
        }

        $url = '/api/query?' . $params;
        if ($this->format === 'html') {
            return $this->call($type, $url);
        }

        return $this->$type($url);
    }

    /**
     * assemble parameters into url string
     */
    protected function assembleParameters($params = [])
    {
        if (!isset($params['serviceUri']) && $this->service) {
            $params['serviceUri'] = $this->service->uri;
        }

        if ($this->oneChannel()) {
            $params['channel'] = $this->channelKeys;
        }

        if ($this->format) {
            $params['format'] = $this->format;
        }

        return http_build_query($params, null, '&');
    }

    /**
     * get contect from call
     * and do base tests
     */
    protected function getContentStructureTested($call)
    {
        // check status code
        $call->seeStatusCode(200);

        // check or the correct nr of channels are in the result
        $structure = $this->channelKeys;
        if ($this->oneChannel()) {
            $structure = [
                $this->channelKeys,
            ];
        }
        $call->seeJsonStructure($structure);

        return $call->decodeResponseJson();
    }

    protected function checkSevenDaysOrNullPerChannel($content)
    {
        // check if we 7 (days) results in all channels or is null
        foreach ($this->channelKeys as $channelKey) {
            switch (true) {
                case $content[$channelKey] === null:
                case count($content[$channelKey]) == 7:
                    $this->assertTrue(true);
                    break;
                default:
                    $this->assertTrue(false);
            }
        }
    }
}
