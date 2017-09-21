<?php

namespace App\Observers;

use App\Models\Channel;

/**
 * Observer on Channel Object
 *
 * These methods will be fired by Eloquent base events.
 * - Before deleting we want to updte the data to the external services
 */
class ChannelObserver
{
    /**
     * process before entitiy is removed
     *
     * trigger jobs of Channel
     * that will sync and remove values from external services
     *
     * @param  Channel $channel
     * @return void
     */
    public function deleting(Channel $channel)
    {
        app('ChannelService')->makeSyncJobsForExternalServices($channel);
    }
}
