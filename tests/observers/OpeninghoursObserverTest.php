<?php

namespace Tests\Observers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursObserverTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @group observers
     */
    public function testItTriggersMakeSyncJobsForExternalServicesWhenOpeninghoursAreSaved()
    {
        $this->app->singleton('OpeninghoursService', function ($app) {
            $mock = $this->createMock(\App\Services\OpeninghoursService::class, ['makeSyncJobsForExternalServices']);
            $mock->expects($this->once())
                ->method('makeSyncJobsForExternalServices');
            return $mock;
        });

        $openinghours        = \App\Models\Openinghours::first();
        $openinghours->label = 'testLabel';
        $openinghours->save();
    }

    /**
     * @test
     * @group observers
     */
    public function testItTriggersMakeSyncJobsForExternalServicesWhenOpeninghoursAreDeleted()
    {
        $this->app->singleton('OpeninghoursService', function ($app) {
            $mock = $this->createMock(\App\Services\OpeninghoursService::class, ['makeSyncJobsForExternalServices']);
            $mock->expects($this->once())
                ->method('makeSyncJobsForExternalServices');
            return $mock;
        });

        $openinghours = \App\Models\Openinghours::first();
        $openinghours->delete();
    }
}
