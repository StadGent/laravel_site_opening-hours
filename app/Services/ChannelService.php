<?php

namespace App\Services;

use App\Jobs\DeleteLodChannel;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Channel;

/**
 * Internal Business logic Service for Channel
 */
class ChannelService
{
    /**
     * Singleton class instance.
     *
     * @var ChannelService
     */
    private static $instance;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * Private contructor for Singleton pattern
     */
    private function __construct()
    {
        $this->queueService = app('QueueService');
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return ChannelService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        self::$instance->serviceModel = null;

        return self::$instance;
    }

    /**
     * Creat Jobs to sync data to external services
     *
     * Make job for VESTA update when service hase vesta source.
     * Make job delete LOD
     *
     * @param  Channel $channel
     * @return void
     */
    public function makeSyncJobsForExternalServices(Channel $channel)
    {
        $service = $channel->service;

        // Update VESTA if the service is linked to a VESTA UID
        if (!empty($service) && $service->source == 'vesta') {
            $job = new UpdateVestaOpeninghours($service->identifier, $service->id);
            $this->queueService->addJobToQueue($job, get_class($service), $service->id);
        }

        // Remove the LOD repository
        $job = new DeleteLodChannel($service->id, $channel->id);
        $this->queueService->addJobToQueue($job, get_class($channel), $channel->id);
    }
}
