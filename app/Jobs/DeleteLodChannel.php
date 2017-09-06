<?php

namespace App\Jobs;

use App\Repositories\LodOpeninghoursRepository;
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        app(LodOpeninghoursRepository::class)->deleteChannel($this->channelId);
    }
}
