<?php

namespace App\Listeners;

use App\Events\OpeninghoursUpdated;
use App\Repositories\OpeninghoursRepository;

class HandleUpdatedOpeninghours
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(OpeninghoursRepository $openinghours)
    {
        $this->openinghours = $openinghours;
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
            \Log::info('UPDATED!');
        }
    }
}
