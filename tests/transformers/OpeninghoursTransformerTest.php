<?php

namespace Tests\Transformers;

use App\Http\Controllers\QueryController;
use App\Http\Transformers\OpeninghoursTransformer;
use App\Models\Service;
use App\Services\LocaleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursTransformerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var LocaleService
     */
    private $localeService;

    /**
     * @var Service
     */
    private $service;

    public function setup()
    {
        parent::setup();

        $this->localeService = app('LocaleService');
        $this->localeService->setDateFormat('d-m-Y');
        $this->localeService->setTimeFormat('H:i');
    }

    /**
     * @test
     * @group content
     */
    public function testTransformHtmlTextDay()
    {
        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart((new Carbon('2017-01-01'))->startOfDay());
        $transformer->setEnd((new Carbon('2017-01-07'))->endOfDay());
        $transformer->setCalendarLength(QueryController::CALENDAR_LENGTH_DAY);
        $transformer->setLocaleService($this->localeService);
        $actual = response()->collection($transformer, $service->channels)->content();
        $content = file_get_contents(__DIR__ . '/../data/transformers/html/openinghours/day.html');
        $expected = str_replace([PHP_EOL,' '],['',''],$content);
        $actual = str_replace([PHP_EOL,' '],['',''],$actual);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testTransformHtmlTextMultipleDays()
    {
        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart((new Carbon('2017-01-01'))->startOfDay());
        $transformer->setEnd((new Carbon('2017-01-31'))->endOfDay());
        $transformer->setCalendarLength(QueryController::CALENDAR_LENGTH_MULTIPLE_DAYS);
        $transformer->setLocaleService($this->localeService);
        $actual = response()->collection($transformer, $service->channels)->content();
        $content = file_get_contents(__DIR__ . '/../data/transformers/html/openinghours/multiple_days.html');
        $expected = str_replace([PHP_EOL,' '],['',''],$content);
        $actual = str_replace([PHP_EOL,' '],['',''],$actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testTransformHtmlTextMonth()
    {
        $service = Service::first();
        $this->localeService->setLocale('nl');

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart((new Carbon('2017-01-01'))->startOfDay());
        $transformer->setEnd((new Carbon('2017-01-31'))->endOfDay());
        $transformer->setCalendarLength(QueryController::CALENDAR_LENGTH_MONTH);
        $transformer->setLocaleService($this->localeService);
        $actual = response()->collection($transformer, $service->channels)->content();
        $content = file_get_contents(__DIR__ . '/../data/transformers/html/openinghours/month.html');
        $expected = str_replace([PHP_EOL,' '],['',''],$content);
        $actual = str_replace([PHP_EOL,' '],['',''],$actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testTransformPlainTextCollection()
    {
        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart((new Carbon('2017-09-15'))->startOfDay());
        $transformer->setEnd((new Carbon('2017-09-15'))->endOfDay());
        $transformer->setLocaleService($this->localeService);

        $actual = $transformer->transformPlainTextCollection($service->channels);

        $actual = str_replace(PHP_EOL, '', $actual);
        $actual = str_replace('=', '', $actual);

        $expected = '';
        foreach ($service->channels as $channel) {
            $expected .= $channel->label . ":";
            $expected .= "vrijdag 15-09-2017: 09:00-12:00 en 13:00-17:00";
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     * TODO : refactor test
     */
    public function testTransformJsonCollection()
    {
        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart((new Carbon('2017-09-15'))->startOfDay());
        $transformer->setEnd((new Carbon('2017-09-15'))->endOfDay());
        $transformer->setLocaleService($this->localeService);

        $actual = $transformer->transformJsonCollection($service->channels);
        $content = file_get_contents(__DIR__ . '/../data/transformers/json/openinghours/transformJsonCollection.json');
        $expected = json_encode(json_decode($content, true));
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @group content
     */
    public function testIsOpenNowGivesOpenGeslotenOrNullPerChannel()
    {

        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(true);
        $transformer->setService($service);
        $transformer->setStart(Carbon::now());
        $transformer->setEnd(Carbon::now()->addMinute(1));
        $transformer->setLocaleService($this->localeService);

        $data = $transformer->transformJsonCollection($service->channels);
        $dataArr = json_decode($data, true);

        // check or all channels have a 'open' or 'gesloten' value
        foreach ($dataArr as $channelBlock) {
            $this->assertContains($channelBlock['openNow']['label'], [
                trans('openinghourApi.CLOSED'),
                trans('openinghourApi.OPEN'),
                null,
            ]);
        }
    }

    /**
     * @todo setup mockers =>
     * Openinghours that simulate edge cases
     *     (=> use 12 'o clock' lunch break events)
     *     => just before close = open
     *     => right on close = closed
     *     => user in other time region
     *     => daylight savings diffs
     */
    public function dateProvider()
    {
        return [
            [new Carbon('2017-05-17 00:00:00'), new Carbon('2017-05-17 00:01:00')],
            // nighttime to see closed values
            [new Carbon('2017-05-17 10:00:00'), new Carbon('2017-05-17 11:00:00')],
            // daytime to see open values
            [new Carbon('2017-05-17 11:59:00'), new Carbon('2017-05-17 12:00:00')],
            // almost midday to see lunch closed values not yet
            [new Carbon('2017-05-17 12:02:00'), new Carbon('2017-05-17 12:30:00')],
            // midday to see lunch closed values
            [new Carbon('2017-05-17 00:00:00'), new Carbon('2017-05-17 23:59:59')],
            // regular one day
            [new Carbon('2017-05-15 00:00:00'), new Carbon('2017-05-21 23:59:59')],
            // regular one week
            [new Carbon('2017-05-01 00:00:00'), new Carbon('2017-05-31 23:59:59')],
            // regular one month
            // [new Carbon('2017-01-01 00:00:00'), new Carbon('2017-12-31 23:59:59')], // regular one year NOT DONE... TOOOO long
            [new Carbon('2016-02-29 00:00:00'), new Carbon('2016-02-29 23:59:59')],
            // scary day (of leap year)
            [new Carbon('2017-12-13 00:00:00'), new Carbon('2018-01-03 23:59:59')],
            // end or year => will need multiple calendars in week/fullWeek calculations
            [Carbon::now()->addYear()->startOfDay(), Carbon::now()->addYear()->endOfDay()],
            // a day in the future
            [Carbon::now()->subYear()->startOfDay(), Carbon::now()->subYear()->endOfDay()],
            // a day in the past
            [Carbon::now()->addYear(125)->startOfDay(), Carbon::now()->addYear(125)->addDay()->endOfDay()],
            // far future (propably without available openinghours)
            [Carbon::now()->subYear(35)->startOfDay(), Carbon::now()->subYear(35)->addDay()->endOfDay()],
            // far past (propably without available openinghours)
        ];
    }

    /**
     * @test
     * @group content
     * @dataProvider dateProvider
     * @todo  make more tests on this
     */
    public function testCollectData($start, $end)
    {
        $service = Service::first();

        $transformer = new OpeninghoursTransformer();
        $transformer->setIncludeIsOpenNow(false);
        $transformer->setService($service);
        $transformer->setStart($start);
        $transformer->setEnd($end);
        $transformer->setLocaleService($this->localeService);

        $data = $transformer->transformJsonCollection($service->channels);
        $dataArr = json_decode($data, true);

        // check or all channels have a 'open' or 'gesloten' value
        foreach ($dataArr as $channelBlock) {
            $actual = 0;
            if (isset($channelBlock['openinghours'])) {
                $actual = count($channelBlock['openinghours']);
            }

            $expected = $start->diffInDays($end) + 1;

            $this->assertEquals($expected, $actual);
        }
    }
}
