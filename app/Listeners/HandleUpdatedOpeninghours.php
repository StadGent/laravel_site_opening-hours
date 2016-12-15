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
     * @param  OpeninghoursUpdated $event
     * @return void
     */
    public function handle(OpeninghoursUpdated $event)
    {
        $openinghours = $this->openinghours->getById($event->getOpeninghoursId());
    }
}
