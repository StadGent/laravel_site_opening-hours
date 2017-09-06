<?php

namespace App\Listeners;

use App\Events\ChannelDeleted;
use App\Jobs\DeleteLodChannel;
use App\Jobs\UpdateVestaOpeninghours;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;

class HandleDeletedChannel
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
    public function handle(ChannelDeleted $event)
    {
        $channel = $event->getChannel();

        $service = $channel['service'];

        // Update VESTA if the service is linked to a VESTA UID
        if (! empty($service) && $service['source'] == 'vesta') {
            dispatch((new UpdateVestaOpeninghours($service['identifier'], $service['id'])));
        }

        // Update the LOD repository with the new openinghours information
        dispatch(new DeleteLodChannel($service['id'], $channel['id']));
    }
}
