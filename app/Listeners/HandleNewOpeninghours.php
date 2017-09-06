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
     * TODO: this listener is probably never necessary, when the calendar is created in the
     * front-end, the first thing a user needs to do is confirm the base layer, triggering an update
     * which is handled by a different handler.
     * @param  OpeninghoursCreated $event
     * @return void
     */
    public function handle(OpeninghoursCreated $event)
    {
        $openinghours = $this->openinghours->getById($event->getOpeninghoursId());
    }
}
