<?php

namespace Tests\Services;

use App\Jobs\DeleteLodChannel;
use App\Jobs\UpdateVestaOpeninghours;
use App\Services\ChannelService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelServiceTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var mixed
     */
    private $channelService;

    public function setUp(): void
    {
        parent::setUp();
        $this->channelService = app('ChannelService');
    }

    /**
     * @test
     * @group jobs
     */
    public function testConnection()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        $this->expectsJobs(DeleteLodChannel::class);

        $channel = \App\Models\Channel::first();
        $channel->service->source = 'vesta';
        $this->channelService->makeSyncJobsForExternalServices($channel);
    }
}
