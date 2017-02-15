<?php

namespace App\Listeners;

use App\Events\OpeninghoursUpdated;
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
        // otherwise, don't do anything
        if ($this->openinghours->isActive($event->getOpeninghoursId())) {
            $openinghours = $this->openinghours->getById($event->getOpeninghoursId());

            $service = $this->getServiceThroughChannel($openinghours['channel_id']);

            if (! empty($service) && $service['source'] == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service['identifier'], $service['id'])));
            }
        }
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
