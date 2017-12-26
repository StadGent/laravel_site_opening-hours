<?php

namespace App\Jobs;

use App\Models\Channel;
use App\Repositories\LodOpeninghoursRepository;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteLodChannel extends BaseJob implements ShouldQueue
{
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
        parent::__construct();
        $this->serviceId = $serviceId;
        $this->channelId = $channelId;

        $this->extModelClass = Channel::class;
        $this->extId = $channelId;
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
            $this->letsFail();
        }
        $this->letsFinish();
    }
}
