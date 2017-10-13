<?php

namespace Tests\Services;

use App\Jobs\DeleteLodChannel;
use App\Jobs\UpdateVestaOpeninghours;
use App\Services\ChannelService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelServiceTest extends \TestCase
{
    use DatabaseTransactions;

    private $channelService;

    public function setup()
    {
        parent::setUp();
        $this->channelService = new ChannelService();
    }

    /**
     * @test
     * @group jobs
     */
    public function testItTriggersSyncDeleteJobsWhenChannelsAreDeleted()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(DeleteLodChannel::class);

        $channel                  = \App\Models\Channel::first();
        $channel->service->source = 'vesta';
        $this->channelService->makeSyncJobsForExternalServices($channel);
    }
}
