<?php

namespace App\Listeners;

use App\Events\OpeninghoursDeleted;
use App\Jobs\DeleteLodOpeninghours;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;
use App\Jobs\UpdateVestaOpeninghours;

class HandleDeletedOpeninghours
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
    public function handle(OpeninghoursDeleted $event)
    {
        // If the openinghours object represented the active openinghours,
        // update the VESTA openinghours of the service entirely, if that service
        // is linked to a VESTA UID otherwise, don't do anything
        $openinghours = $event->getOpeninghours();

        $service = $openinghours['channel']['service'];

        if ($event->wasOpeninghoursActive()) {
            // Update VESTA if the service is linked to a VESTA UID
            if (! empty($service) && $service['source'] == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service['identifier'], $service['id'])));
            }
        }

        // Update the LOD repository with the new openinghours information
        dispatch(new DeleteLodOpeninghours($service['id'], $openinghours['id']));
    }
}
