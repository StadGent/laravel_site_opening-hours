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
    protected $serviceId;

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
        $service = \App\Models\Service::first();
        $this->serviceId = $service->id;
        $this->channelKeys = $service->channels->pluck('id');
    }

    public function requestTypeProvider()
    {
        $dateParam = date('Y-m-d');

        return [
            [['type' => 'open-now']],
            [['type' => 'open-now', 'format' => 'json-ld']],
            [['type' => 'open-now', 'format' => 'html']],
            [['type' => 'open-now', 'format' => 'text']],
            [['type' => 'openinghours', 'from' => $dateParam, 'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days'))]],
            [['type' => 'openinghours', 'from' => $dateParam, 'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'json-ld']],
            [['type' => 'openinghours', 'from' => $dateParam, 'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'html']],
            [['type' => 'openinghours', 'from' => $dateParam, 'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'text']],
            [['type' => 'openinghours', 'period' => 'day', 'date' => $dateParam]],
            [['type' => 'openinghours', 'period' => 'day', 'date' => $dateParam, 'format' => 'json-ld']],
            [['type' => 'openinghours', 'period' => 'day', 'date' => $dateParam, 'format' => 'html']],
            [['type' => 'openinghours', 'period' => 'day', 'date' => $dateParam, 'format' => 'text']],
            [['type' => 'openinghours', 'period' => 'week', 'date' => $dateParam]],
            [['type' => 'openinghours', 'period' => 'month', 'date' => $dateParam]],
            [['type' => 'openinghours', 'period' => 'year', 'date' => $dateParam]],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestTypeProvider
     **/
    public function testValidateNoServiceArgumentIsAPathNotFoundError($typeParams)
    {
        $this->serviceId = null;
        $typeParams['format'] = 'json';
        $call = $this->doRequest('GET', $typeParams);
        $call->seeStatusCode(404);
        $call->seeJsonEquals([
            "error" => [
                "code" => "PathNotFound",
                "message" => "The requested path could not match a route in the API",
                "target" => "query",
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestTypeProvider
     */
    public function testValidateInvallidServiceIdentifierIsAModelNotFoundError($typeParams)
    {
        $this->serviceId = 'notAServiceId';
        $typeParams['format'] = 'json';
        $call = $this->doRequest('GET', $typeParams);
        $call->seeStatusCode(422);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ModelNotFound",
                "message" => "Service model is not found with given identifier",
                "target" => "Service",
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateServiceWithoutChannelsReturnsNotFoundError()
    {
        $this->serviceId = factory(\App\Models\Service::class)->create(['label' => 'testChildlessService'])->id;
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'day', 'date' => date('Y-m-d')]);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The selected service 'testChildlessService' is not available yet.",
                        "target" => "Service",
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateOpeninhoursRequiresFromUntilParameters()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours']);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The 'from' argument is required.",
                        "target" => "from",
                    ],
                    [
                        "code" => "NotValidParameter",
                        "message" => "The 'until' argument is required.",
                        "target" => "until",
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateOpeninhoursFromUntilParametersMustBeVallidDateFormat()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'from' => 'notADate', 'until' => 'notADate']);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The from is not a valid date.",
                        "target" => "from",
                    ],
                    [
                        "code" => "NotValidParameter",
                        "message" => "The until is not a valid date.",
                        "target" => "until",
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateOpeninhoursFromMustComeBeforUntil()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'from' => '2017-01-01', 'until' => '2016-01-01']);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The until must be a date after from.",
                        "target" => "until",
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateOpeninhoursFromUntilParametersMustBeWithinOneYear()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'from' => '2017-01-01', 'until' => '2018-01-05']);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The difference between from and till may only be max 366 days.",
                        "target" => "until",
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateOpeninhoursWithPeriodRequiresDateParameter()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'day']);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotValidParameter",
                "message" => "Paramters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The 'date' argument is required.",
                        "target" => "date",
                    ],
                ],
            ],
        ]);
    }

    public function requestDateTypes()
    {
        $dateParam = date('Y-m-d');

        return [
            [[
                'from' => '2017-01-01',
                'until' => '09-01-2017',
            ]],
            [[
                'from' => 'last day of August 2017',
                'until' => 'first day of September 2017',
            ]],
            [[
                'from' => date(DATE_RFC2822, strtotime('2017-09-15')), // 'Fri, 15 Sep 2017 00:00:00 +0000'
                'until' => date(DATE_RFC1036, strtotime('2017-09-16')), //'Sat, 16 Sep 17 00:00:00 +0000'
            ]],
            [[
                'from' => date(DATE_RFC850, strtotime('2017-09-01')), //  'Friday, 01-Sep-17 00:00:00 UTC'
                'until' => date(DATE_W3C, strtotime('2017-09-02')), //    '2017-09-02T00:00:00+00:00'
            ]],
            [[
                'period' => 'day',
                'date' => '2017-01-01',
            ]],
            [[
                'period' => 'month',
                'date' => 'sept-2017',
            ]],
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestDateTypes
     */
    public function testValidateOpeninhoursDatesCanHandlePHPDateFormats($dateTypes)
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours'] + $dateTypes);
        $call->seeStatusCode(200);
    }

    /**
     * @test
     * @group content
     * @dataProvider requestTypeProvider
     **/
    public function testItHasOnlyOneChannelkeyWhenChannelParamIsGiven($typeParams)
    {
        $this->channelKeys = $this->channelKeys->first();
        $call = $this->doRequest('GET', $typeParams);
        if (!isset($typeParams['format']) || $typeParams['format'] === 'json') {
            $this->getContentStructureTested($call);
        }

    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeOpenNow()
    {
        $call = $this->doRequest('GET', ['type' => 'open-now']);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeDayWithDateParam()
    {
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'day', 'date' => date('d-m-Y')]);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysPerChannelOnTypeWeek()
    {
        $dateParam = date('d-m-Y', strtotime('tomorrow'));
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'week', 'date' => date('d-m-Y')]);
        $content = $this->getContentStructureTested($call);

        foreach ($content as $channelBlock) {
            $this->assertCount(7, $channelBlock['openinghours']);
        }
    }

    /**
     * @test
     * @group content
     * Setup data closing day on each first monday of the month
     */
    public function testItGivesClosedForEachFirstMondayOfTheMonth()
    {
        $firstMondayOfSept2017 = '04-09-2017';
        $fullWeekCall = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'week', 'date' => $firstMondayOfSept2017]);
        $content = $this->getContentStructureTested($fullWeekCall);

        foreach ($content as $channelBlock) {
            // first 0 key is monday
            $this->assertFalse($channelBlock['openinghours']['2017-09-04']['open']);
        }
    }

    /**
     * returns true if we have a value in the channel parameter
     * @return boolean
     */
    public function oneChannel()
    {
        return count($this->channelKeys) === 1;
    }

    /**
     * do request according to the given format
     */
    public function doRequest($type, $params = null)
    {

        $path = '/api/services/' . $this->serviceId;

        if ($this->oneChannel()) {
            $path .= '/channels/' . $this->channelKeys;
        }

        if (is_array($params)) {
            $path .= '/' . $params['type'];
            unset($params['type']);

            if (isset($params['period'])) {
                $path .= '/' . $params['period'];
                unset($params['period']);
            }

            if (count($params)) {
                $path .= '?' . http_build_query($params, null, '&');
            }
        }

        if (isset($params['format']) && $params['format'] !== 'json') {
            return $this->call($type, $path);
        }

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
