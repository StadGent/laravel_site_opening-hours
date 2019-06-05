<?php

namespace Tests\Services;

use App\Jobs\UpdateVestaOpeninghours;
use App\Models\QueuedJob;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class QueueServiceTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var mixed
     */
    private $channelService;

    public function setup()
    {
        parent::setUp();
        $this->queueService = app(QueueService::class);
    }

    /**
     * @test
     * @group jobs
     */
    public function testAddJobGoesInQueueAndRemoveJobGoesOutOfQueue()
    {
        $job = new UpdateVestaOpeninghours('jahoo', 5);
        $originalJobs = QueuedJob::all()->count();
        $this->queueService->addJobToQueue($job, Service::class, 5);
        $this->assertEquals($originalJobs + 1, QueuedJob::all()->count());

        $this->queueService->removeJobFromQueue($job, Service::class, 5);
        $this->assertEquals($originalJobs, QueuedJob::all()->count());
    }
}
