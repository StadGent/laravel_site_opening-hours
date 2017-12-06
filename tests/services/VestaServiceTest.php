<?php

namespace Tests\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Openinghours;
use App\Models\QueuedJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VestaServiceTest extends \TestCase
{
    use DatabaseTransactions;
    /**
     * @var string
     */
    private $testGuid = "191e1f2a-49b5-e111-b173-0050569805c9"; // => "Test Steven 13 juni";

    /**
     * @var string
     */
    private $testData = 'SomeDummyData'; // => "Test Steven 13 juni";

    /**
     * @return null
     */
    public function setup()
    {
        parent::setUp();
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $this->vestaService = app('VestaService');
    }

    /**
     * @test
     * @group validation
     */
    public function testItThrowsAnErrorWhenNoGuidInUpdateRequest()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->setExpectedException('Exception', 'A guid is required to update the data in VESTA');
        $this->vestaService->updateOpeninghours('');
    }

    /**
     * @test
     * @group validation
     */
    public function testItThrowsAnErrorWhenNoGuidIngetRequest()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->setExpectedException('Exception', 'A guid is required to request the data from VESTA');
        $this->vestaService->getOpeningshoursByGuid('');
    }

    /**
     * @test
     * @group validation
     */
    public function testItThrowsAnErrorWhenInvallidIdentifier()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $this->setExpectedException('SoapFault');
        $this->vestaService->updateOpeninghours('inVallidIdentifier', 'SomeDummyData');
    }

    /**
     * @test
     * @group content
     */
    public function testItReturnsATrueOnSuccesAndValueIsFoundInVesta()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }
        $testData = $this->testData . uniqid();

        $resultInsert = $this->vestaService->updateOpeninghours($this->testGuid, $testData);
        $this->assertTrue($resultInsert);

        $resultRead = $this->vestaService->getOpeningshoursByGuid($this->testGuid);
        $this->assertEquals($testData, $resultRead);
    }

    /**
     * @test
     * @group functionality
     */
    public function testMakeSyncJobsForExternalServicesWithWrongTypeFails()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $this->setExpectedException('Exception');
        $openinghours = Openinghours::first();
        $this->vestaService->makeSyncJobsForExternalServices($openinghours, 'thisIsNotAType');
    }

    /**
     * @test
     * @group jobs
     */
    public function testItTriggersSyncUpdateJobsWhenOpeninghoursAreSaved()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(UpdateLodOpeninghours::class);

        $openinghours = Openinghours::first();
        $openinghours->channel->service->source = 'vesta';
        $openinghours->label = 'testLabel';
        $this->vestaService->makeSyncJobsForExternalServices($openinghours, 'update');
    }

    /**
     * @test
     * @group jobs
     */
    public function testItTriggersSyncDeleteJobsWhenOpeninghoursAreDeleted()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(DeleteLodOpeninghours::class);

        $openinghours = Openinghours::first();
        $openinghours->channel->service->source = 'vesta';
        $this->vestaService->makeSyncJobsForExternalServices($openinghours, 'delete');
    }

    /**
     * @test
     * @group jobs
     */
    public function testTriggerJobOnlyOnce()
    {
        if (env('APP_SKIP_TRAVIS_TEST')) {
            return;
        }

        $jobsNrOriginal = QueuedJob::all()->count();

        $openinghours = Openinghours::first();
        $openinghours->channel->service->source = 'vesta';
        $this->vestaService->makeSyncJobsForExternalServices($openinghours, 'update');

        $jobsNrOneAdded = QueuedJob::all()->count();
        $this->assertEquals($jobsNrOriginal + 1, $jobsNrOneAdded);

        $this->vestaService->makeSyncJobsForExternalServices($openinghours, 'update');
        $jobsNrStillTheSame = QueuedJob::all()->count();
        $this->assertEquals($jobsNrOneAdded, $jobsNrStillTheSame);
    }
}
