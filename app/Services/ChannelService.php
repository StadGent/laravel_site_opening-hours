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
            dispatch((new UpdateVestaOpeninghours($service->identifier, $service->id)));
        }

        // Remove the LOD repository
        dispatch(new DeleteLodChannel($service->id, $channel->id));
    }
}
