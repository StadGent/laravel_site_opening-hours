<?php

namespace Tests\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Services\ICalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursServiceTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var OpeninghoursService
     */
    private $OHService;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $ICalServiceMock;

    /**
     * @return void
     */
    public function setup()
    {
        parent::setUp();
        $this->OHService = app('OpeninghoursService');

        $this->ICalServiceMock = $this->createMock(ICalService::class, ['createIcalFromCalendar', 'extractDayInfo']);
        $this->ICalServiceMock->expects($this->any())
            ->method('createIcalFromCalendar')
            ->willReturn(false);

        $this->ICalServiceMock->expects($this->any())
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

        $this->app->instance('ICalService', $this->ICalServiceMock);
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
            [new Carbon('2017-05-17 00:00:00'), new Carbon('2017-05-17 00:01:00')], // nighttime to see closed values
            [new Carbon('2017-05-17 10:00:00'), new Carbon('2017-05-17 11:00:00')], // daytime to see open values
            [new Carbon('2017-05-17 11:59:00'), new Carbon('2017-05-17 12:00:00')], // almost midday to see lunch closed values not yet
            [new Carbon('2017-05-17 12:02:00'), new Carbon('2017-05-17 12:30:00')], // midday to see lunch closed values
            [new Carbon('2017-05-17 00:00:00'), new Carbon('2017-05-17 23:59:59')], // regular one day
            [new Carbon('2017-05-15 00:00:00'), new Carbon('2017-05-21 23:59:59')], // regular one week
            [new Carbon('2017-05-01 00:00:00'), new Carbon('2017-05-31 23:59:59')], // regular one month
            [new Carbon('2017-01-01 00:00:00'), new Carbon('2017-12-31 23:59:59')], // regular one year
            [new Carbon('2016-02-29 00:00:00'), new Carbon('2016-02-29 23:59:59')], // scary day (of leap year)
            [new Carbon('2017-12-13 00:00:00'), new Carbon('2018-01-03 23:59:59')], // end or year => will need multiple calendars in week/fullWeek calculations
            [Carbon::now()->startOfDay(), Carbon::now()->addYear()->endOfDay()], // in the future
            [Carbon::now()->subYear()->startOfDay(), Carbon::now()->endOfDay()], // in the past
            [Carbon::now()->addYear(125)->startOfDay(), Carbon::now()->addYear(125)->addDay()->endOfDay()], // far future (propably without available openinghours)
            [Carbon::now()->subYear(125)->startOfDay(), Carbon::now()->subYear(125)->addDay()->endOfDay()], // far past (propably without available openinghours)
        ];
    }

    /**
     * @test
     * @group content
     */
    public function testIsOpenNowGivesOpenGeslotenOrNullPerChannel()
    {
        $service = \App\Models\Service::first();
        $this->OHService->isOpenNow($service);
        // check or all channels have a 'open' or 'gesloten' value
        foreach ($this->OHService->getData() as $channelBlock) {
            $this->assertContains($channelBlock->openNow->label, [trans('openinghourApi.CLOSED'), trans('openinghourApi.OPEN'), null]);
        }
    }

    /**
     * @test
     * @group content
     * @dataProvider dateProvider
     * @todo  make more tests on this
     */
    public function testCollectData($start, $end)
    {
        $service = \App\Models\Service::first();
        $this->OHService->collectData($start, $end, $service);
        foreach ($this->OHService->getData() as $data) {
            $this->assertCount($start->diffInDays($end) + 1, $data->openinghours);
        }
    }

    /**
     * @test
     * @group functionality
     */
    public function testMakeSyncJobsForExternalServicesWithWrongTypeFails()
    {
        $this->setExpectedException('Exception');
        $openinghours = \App\Models\Openinghours::first();
        $this->OHService->makeSyncJobsForExternalServices($openinghours, 'thisIsNotAType');
    }

    /**
     * @test
     * @group jobs
     */
    public function testItTriggersSyncUpdateJobsWhenOpeninghoursAreSaved()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(UpdateLodOpeninghours::class);

        $openinghours = \App\Models\Openinghours::first();
        $openinghours->channel->service->source = 'vesta';
        $openinghours->label = 'testLabel';
        $this->OHService->makeSyncJobsForExternalServices($openinghours, 'update');
    }

    /**
     * @test
     * @group jobs
     */
    public function testItTriggersSyncDeleteJobsWhenOpeninghoursAreDeleted()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(DeleteLodOpeninghours::class);

        $openinghours = \App\Models\Openinghours::first();
        $openinghours->channel->service->source = 'vesta';
        $this->OHService->makeSyncJobsForExternalServices($openinghours, 'delete');
    }
}
