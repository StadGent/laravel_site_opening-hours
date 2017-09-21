<?php

namespace Tests\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Services\OpeninghoursService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursServiceTest extends \TestCase
{
    use DatabaseTransactions;

    private $OHService;

    public function setup()
    {
        parent::setUp();
        $this->OHService = new OpeninghoursService();
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
            [new Carbon('2017-05-17')], // regular date (Tuesday)
            [new Carbon('2017-05-17 03:25:16')], // nighttime to see closed values
            [new Carbon('2017-05-17 10:43:02')], // daytime to see open values
            [new Carbon('2017-05-17 11:59:59')], // almost midday to see lunch closed values not yet
            [new Carbon('2017-05-17 12:02:00')], // midday to see lunch closed values
            [new Carbon('2016-02-29')], // scary day (of leap year)
            [new Carbon('2017-12-13')], // end or year => will need multiple calendars in week/fullWeek calculations
            [Carbon::now()->addYear()], // in the future
            [Carbon::now()->subYear()], // in the past
            [Carbon::now()->addYear(125)], // far future (propably without available openinghours)
            [Carbon::now()->subYear(125)], // far past (propably without available openinghours)
        ];
    }

    /**
     * @test
     * @group validation
     **/
    public function testIsOpenNowWithoutServiceFails()
    {
        $this->setExpectedException('Exception');
        $this->OHService->isOpenNow();
    }

    /**
     * @test
     * @group validation
     **/
    public function testIsOpenOnDayWithoutServiceFails()
    {
        $this->setExpectedException('Exception');
        $this->OHService->isOpenOnDay();
    }

    /**
     * @test
     * @group validation
     **/
    public function testIsOpenForNextSevenDaysWithoutServiceFails()
    {
        $this->setExpectedException('Exception');
        $this->OHService->isOpenForNextSevenDays();
    }

    /**
     * @test
     * @group validation
     **/
    public function testIsOpenForFullWeekWithoutServiceFails()
    {
        $this->setExpectedException('Exception');
        $this->OHService->isOpenForFullWeek();
    }

    /**
     * @test
     * @group content
     */
    public function testIsOpenNowGivesOpenGeslotenOrNullPerChannel()
    {
        $this->OHService->setServiceModel(\App\Models\Service::first());
        $this->OHService->isOpenNow();
        // check or all channels have a 'open' or 'gesloten' value
        foreach ($this->OHService->getData() as $channelKey => $data) {
            $this->assertContains($data, [trans('openinghourApi.CLOSED'), trans('openinghourApi.OPEN'), null]);
        }
    }

    /**
     * @test
     * @group content
     * @dataProvider dateProvider
     */
    public function testIsOpenOnDayGivesOpeninghoursGeslotenOrNullPerChannel($startDate)
    {
        $this->OHService->setServiceModel(\App\Models\Service::first());
        $this->OHService->isOpenOnDay($startDate);
        foreach ($this->OHService->getData() as $channelKey => $data) {
            $this->assertTrue($this->checkOpeningHoursContentString($data));
        }
    }
    /**
     * @test
     * @group content
     */
    public function testIsOpenForNextSevenDaysGives()
    {
        $this->OHService->setServiceModel(\App\Models\Service::first());
        $this->OHService->isOpenForNextSevenDays();
        foreach ($this->OHService->getData() as $channelKey => $datedata) {
            if (!$datedata) {
                // no data is good data... or something
                continue;
            }
            foreach ($datedata as $date => $dataString) {
                /**
                 * @todo check date is correct
                 */
                $this->assertTrue($this->checkOpeningHoursContentString($dataString));
            }
        }
    }

    /**
     * @test
     * @group content
     * @dataProvider dateProvider
     */
    public function testIsOpenForFullWeekGives($startDate)
    {
        $this->OHService->setServiceModel(\App\Models\Service::first());
        $this->OHService->isOpenForFullWeek($startDate);
        foreach ($this->OHService->getData() as $channelKey => $datedata) {
            if (!$datedata) {
                // no data is good data... so funny
                continue;
            }
            foreach ($datedata as $date => $dataString) {
                /**
                 * @todo check date is correct
                 */
                $this->assertTrue($this->checkOpeningHoursContentString($dataString));
            }
        }
    }

    /**
     * code reusability that checks or the data string on the day is from the correct format
     * - 'hh:mm - hh:mm' (from - till) format
     * - 'gesloten' (in nl lang)
     * - null (not set)
     *
     * @param  string $dataString  Openinghours string
     * @return boolean             passed or failed
     */
    private function checkOpeningHoursContentString($dataString)
    {
        switch (true) {
            // no openinghours available
            case $dataString === null:
            // is closed that day
            case strpos($dataString, trans('openinghourApi.CLOSED')) !== false:
            // is open that day and shows at least one event of 'fro m - to' hours
            case preg_match('/([01]?[0-9]|2[0-3]):([0-5][0-9])\s-\s([01]?[0-9]|2[0-3]):([0-5][0-9])/', $dataString):
                return true;
                break;
            default:
                return false;
        }
    }

    /**
     * @test
     * @group functionality
     */
    public function testMakeSyncJobsForExternalServicesWithWrongTypeFails()
    {
        $this->setExpectedException('Exception');
        $openinghours        = \App\Models\Openinghours::first();
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

        $openinghours        = \App\Models\Openinghours::first();
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
