<?php

namespace App\Listeners;

use App\Events\OpeninghoursCreated;
use App\Repositories\OpeninghoursRepository;

class HandleNewOpeninghours
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
     * @param  OpeninghoursCreated $event
     * @return void
     */
    public function handle(OpeninghoursCreated $event)
    {
        $openinghours = $this->openinghours->getById($event->getOpeninghoursId());

        dd($openinghours);
    }
}
