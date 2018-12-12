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

    /**
     * requestTypeProvider
     *
     * @return array
     */
    public function requestTypeProvider()
    {
        $dateParam = date('Y-m-d');

        return [
            [['type' => 'open-now']],
            [['type' => 'open-now', 'format' => 'json-ld']],
            [['type' => 'open-now', 'format' => 'html']],
            [['type' => 'open-now', 'format' => 'text']],
            [['type' => 'openinghours', 'from' => $dateParam,
                'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days'))]],
            [['type' => 'openinghours', 'from' => $dateParam,
                'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'json-ld']],
            [['type' => 'openinghours', 'from' => $dateParam,
                'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'html']],
            [['type' => 'openinghours', 'from' => $dateParam,
                'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days')), 'format' => 'text']],
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
    public function testValidateNoServiceArgumentIsANotFoundHttpException($typeParams)
    {
        $this->serviceId = null;
        $typeParams['format'] = 'json';
        $path = $this->assemblePath($typeParams);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(404);
        $call->seeJsonEquals([
            "error" => [
                "code" => "NotFoundHttpException",
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
    public function testValidateInvallidServiceIdentifierIsAModelNotFoundException($typeParams)
    {
        $this->serviceId = 'notAServiceId';
        $typeParams['format'] = 'json';
        $path = $this->assemblePath($typeParams);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(422);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ModelNotFoundException",
                "message" => "Service model is not found with given identifier",
                "target" => "Service",
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestTypeProvider
     */
    public function testValidateServiceWithNotCoupledChannelIsAModelNotFoundException($typeParams)
    {
        $this->serviceId = 2;
        $this->channelKeys = 1;
        $typeParams['format'] = 'json';
        $path = $this->assemblePath($typeParams);

        $call = $this->doRequest('GET', $path);
        $call->seeJsonEquals([
            'error' => [
                'code' => 'ValidationException',
                'message' => 'Parameters did not pass validation',
                'target' => 'parameters',
                'details' => [
                    0 => [
                        'code' => 'ParentChildMismatch',
                        'message' => 'The requested service did not find a match for the given channel identifier',
                        'target' => 'Channel',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group validation
     */
    public function testValidateServiceWithoutChannelsReturnsValidationException()
    {
        $this->serviceId = factory(\App\Models\Service::class)->create(['label' => 'testChildlessService'])->id;
        $path = $this->assemblePath(['type' => 'openinghours', 'period' => 'day', 'date' => date('Y-m-d')]);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
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
    public function testValidateOpeningHoursRequiresFromUntilParameters()
    {
        $path = $this->assemblePath(['type' => 'openinghours']);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
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
    public function testValidateOpeningHoursFromUntilParametersMustBeValidDateFormat()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'from' => 'notADate', 'until' => 'notADate']);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
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
    public function testValidateOpeningHoursFromMustComeBeforeUntilOrBeEqualToUntil()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'from' => '2017-01-01', 'until' => '2016-01-01']);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
                "target" => "parameters",
                "details" => [
                    [
                        "code" => "NotValidParameter",
                        "message" => "The until must be a date after or equal to :date.",
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
    public function testValidateOpeningHoursFromUntilParametersMustBeWithinOneYear()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'from' => '2017-01-01', 'until' => '2018-01-05']);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
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
    public function testValidateOpeningHoursWithPeriodRequiresDateParameter()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'period' => 'day']);
        $call = $this->doRequest('GET', $path);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            "error" => [
                "code" => "ValidationException",
                "message" => "Parameters did not pass validation",
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

    /**
     * requestDateTypes
     *
     * @return array
     */
    public function requestDateTypes()
    {
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
    public function testValidateOpeningHoursDatesCanHandlePHPDateFormats($dateTypes)
    {
        $path = $this->assemblePath(['type' => 'openinghours'] + $dateTypes);
        $call = $this->doRequest('GET', $path);
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
        $path = $this->assemblePath($typeParams);
        $call = $this->doRequest('GET', $path);
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
        $path = $this->assemblePath(['type' => 'open-now']);
        $call = $this->doRequest('GET', $path);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeDayWithDateParam()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'period' => 'day', 'date' => date('d-m-Y')]);
        $call = $this->doRequest('GET', $path);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysPerChannelOnTypeWeek()
    {
        $path = $this->assemblePath(['type' => 'openinghours', 'period' => 'week', 'date' => date('d-m-Y')]);
        $call = $this->doRequest('GET', $path);
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
        $path = $this->assemblePath(['type' => 'openinghours', 'period' => 'week', 'date' => $firstMondayOfSept2017]);
        $call = $this->doRequest('GET', $path);

        $content = $this->getContentStructureTested($call);

        foreach ($content as $channelBlock) {
            // first 0 key is monday
            $this->assertFalse($channelBlock['openinghours'][0]['open']);
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
     * assemble the path on the given params
     */
    protected function assemblePath($params)
    {
        $path = $this->apiUrl . '/services/' . $this->serviceId;

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

        return $path;
    }
}