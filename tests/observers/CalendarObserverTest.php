<?php

namespace Tests\Observers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalendarObserverTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @group observers
     */
    public function testItTriggersMakeSyncJobsForExternalServicesWhenCalendarsAreSaved()
    {
        $this->app->singleton('VestaService', function () {
            $mock = $this->createMock(\App\Services\VestaService::class, ['makeSyncJobsForExternalServices']);
            $mock->expects($this->atLeastOnce())
                ->method('makeSyncJobsForExternalServices');

            return $mock;
        });

        $calendar = \App\Models\Calendar::first();
        $calendar->save();
    }
}
