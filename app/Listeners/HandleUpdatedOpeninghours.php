<?php

namespace App\Listeners;

use App\Events\OpeninghoursUpdated;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;
use App\Services\VestaService;

class HandleUpdatedOpeninghours
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(OpeninghoursRepository $openinghours, ChannelRepository $channels)
    {
        $this->openinghours = $openinghours;
        $this->channels = $channels;
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
        // update the VESTA openinghours of the service entirely
        // otherwise, don't do anything
        if ($this->openinghours->isActive($event->getOpeninghoursId())) {
            $openinghours = $this->openinghours->getById($event->getOpeninghoursId());

            $channel = $this->channels->getById($openinghours['channel_id']);

            $vestaService = new VestaService();
            $vestaService->updateOpeninghours($channel['service_id']);
        }
    }
}
