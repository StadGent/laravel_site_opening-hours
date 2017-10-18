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
        $this->app->singleton('OpeninghoursService', function () {
            $mock = $this->createMock(\App\Services\OpeninghoursService::class, ['makeSyncJobsForExternalServices']);
            $mock->expects($this->once())
                ->method('makeSyncJobsForExternalServices');

            return $mock;
        });

        $calendar = \App\Models\Calendar::first();
        $calendar->save();
    }
}
