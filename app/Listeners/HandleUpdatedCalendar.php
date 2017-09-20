<?php

namespace App\Listeners;

use App\Events\CalendarUpdated;
use App\Events\OpeninghoursUpdated;
use App\Repositories\CalendarRepository;

class HandleUpdatedCalendar
{
    /**
     * @var App\Repositories\CalendarRepository
     */
    private $calendars;

    /**
     * Create the event listener.
     *
     * @param  App\Repositories\CalendarRepository $calendars
     * @return void
     */
    public function __construct(CalendarRepository $calendars)
    {
        $this->calendars = $calendars;
    }

    /**
     * Handle the event.
     *
     * @param  CalendarUpdated $event
     * @return void
     */
    public function handle(CalendarUpdated $event)
    {
        $calendar = $this->calendars->getById($event->getCalendarId());

        // event(new OpeninghoursUpdated($calendar['openinghours_id']));
    }
}
