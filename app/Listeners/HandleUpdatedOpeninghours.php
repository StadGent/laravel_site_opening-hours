<?php

namespace App\Listeners;

use App\Events\OpeninghoursUpdated;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;

class HandleUpdatedOpeninghours
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(OpeninghoursRepository $openinghours, ChannelRepository $channels, ServicesRepository $services)
    {
        $this->openinghours = $openinghours;
        $this->channels = $channels;
        $this->services = $services;
    }

    /**
     * Handle the event.
     *
     * @param  UpdatedOpeninghours $event
     * @return void
     */
    public function handle(OpeninghoursUpdated $event)
    {
        // If the openinghours object represented the active openinghours,
        // update the VESTA openinghours of the service entirely, if that service
        // is linked to a VESTA UID
        // otherwise, don't do anything, but make sure the updated openinghours are stored in LOD
        $openinghours = $this->openinghours->getById($event->getOpeninghoursId());
        $service = $this->getServiceThroughChannel($openinghours['channel_id']);

        if ($this->openinghours->isActive($event->getOpeninghoursId())) {
            // Update VESTA if the service is linked to a VESTA UID
            if (! empty($service) && $service['source'] == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service['identifier'], $service['id'])));
            }
        }

        // Update the LOD repository with the new openinghours information
        dispatch(new UpdateLodOpeninghours($service['id'], $event->getOpeninghoursId(), $openinghours['channel_id']));
    }

    /**
     * Return the service that is linked to the channelId
     *
     * @param  int   $channelId
     * @return array
     */
    private function getServiceThroughChannel($channelId)
    {
        $channel = $this->channels->getById($channelId);

        return $this->services->getById($channel['service_id']);
    }
}
