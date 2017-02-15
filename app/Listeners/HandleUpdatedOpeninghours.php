<?php

namespace App\Listeners;

use App\Events\OpeninghoursUpdated;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;
use App\Services\VestaService;

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

            if (! empty($service) && $services['source'] == 'vesta') {
                $vestaService = new VestaService();
                $vestaService->updateOpeninghours($service['id'], $service['identifier']);
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
        $channel = $this->channels->getById($openinghours['channel_id']);

        return $this->services->getById($channel['service_id']);
    }
}
