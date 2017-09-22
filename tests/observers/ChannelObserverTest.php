<?php

namespace Tests\Observers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelObserverTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @group observers
     */
    public function testItTriggersMakeSyncJobsForExternalServicesWhenChannelIsDeleted()
    {
        $this->app->singleton('ChannelService', function () {
            $mock = $this->createMock(\App\Services\ChannelService::class, ['makeSyncJobsForExternalServices']);
            $mock->expects($this->once())
                ->method('makeSyncJobsForExternalServices');
            return $mock;
        });

        $channel = \App\Models\Channel::first();
        $channel->delete();
    }
}
