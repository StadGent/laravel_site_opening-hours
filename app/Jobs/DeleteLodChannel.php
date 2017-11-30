<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Repositories\LodOpeninghoursRepository;
use App\Services\QueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteLodChannel implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $serviceId;

    /**
     * @var int
     */
    protected $channelId;

    /**
     * @var QueueService
     */
    private $queueService;


    /**
     * Create a new job instance.
     *
     * @param int $serviceId
     * @param int $channelId
     *
     * @return void
     */
    public function __construct($serviceId, $channelId)
    {
        $this->serviceId = $serviceId;
        $this->channelId = $channelId;
        $this->queueService = app('QueueService');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = app(LodOpeninghoursRepository::class)->deleteChannel($this->channelId);
        if (!$result) {
            $this->fail(new \Exception(sprintf(
                'The %s job failed with service id %s and channel id %s. Check the logs for details',
                static::class,
                $this->serviceId,
                $this->channelId
            )));
        }
        $this->queueService->removeJobFromQueue($this, Channel::class, $this->channelId);
    }
}
