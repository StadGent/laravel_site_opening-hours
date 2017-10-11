<?php

namespace Tests\Controllers;

use App\Services\ICalService;
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
     * @return mixed
     */
    private function setupICalServiceServiceMock()
    {

        $ICalServiceMock = $this->createMock(ICalService::class, ['createIcalFromCalendar', 'extractDayInfo']);
        $ICalServiceMock->expects($this->any())
            ->method('createIcalFromCalendar')
            ->willReturn(false);

        $ICalServiceMock->expects($this->any())
            ->method('extractDayInfo')
            ->with($this->anything())
            ->will($this->returnCallback(function () {
                $shuffle = [
                    [
                        ['from' => '08:00', 'until' => '12:00'], // morning shift
                        ['from' => '12:30', 'until' => '17:00'], // afternoon shift
                    ],
                    [
                        ['from' => '08:00', 'until' => '16:00'], // fulltime
                    ],
                    [
                        ['from' => '20:00', 'until' => '23:59'], // night shift
                    ],
                    false, // holiday :-D
                ];

                return $shuffle[rand(0, 3)];
            }));

        $this->app->instance('ICalService', $ICalServiceMock);
    }

    public function requestTypeProvider()
    {
        $dateParam = date('Y-m-d');

        return [
            [['type' => 'open-now']],
            [['type' => 'openinghours', 'from' => $dateParam, 'until' => date('Y-m-d', strtotime($dateParam . ' + 10 days'))]],
            [['type' => 'openinghours', 'period' => 'day', 'date' => $dateParam]],
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
        $this->setupICalServiceServiceMock();
        // undo service setter
        // will be restored for next test by setup
        $this->serviceId = null;
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
        $this->setupICalServiceServiceMock();
        $this->serviceId = 'notAServiceId';
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        $this->setupICalServiceServiceMock();
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
        // {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}&format={format}
        $this->setupICalServiceServiceMock();
        $this->channelKeys = $this->channelKeys->first();
        $call = $this->doRequest('GET', $typeParams);
        $content = $this->getContentStructureTested($call);

    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeOpenNow()
    {
        // {host}/api/query?q=now&serviceUri={serviceUri}&format={format}
        $this->setupICalServiceServiceMock();
        $call = $this->doRequest('GET', ['type' => 'open-now']);
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsGoodResultsOnTypeDayWithDateParam()
    {
        // {host}/api/query?q=day&date={mm-dd-yyyy}&serviceUri={serviceUri}&format={format}
        $this->setupICalServiceServiceMock();
        $call = $this->doRequest('GET', ['type' => 'openinghours', 'period' => 'day', 'date' => date('d-m-Y')]);
        $this->setupICalServiceServiceMock();
        $this->getContentStructureTested($call);
    }

    /**
     * @test
     * @group content
     */
    public function testItGivesSevenDaysPerChannelOnTypeWeek()
    {
        // {host}/api/query?q=week&serviceUri={serviceUri}&format={format}
        $this->setupICalServiceServiceMock();
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

        if ($this->format === 'html') {
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
