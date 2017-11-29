<?php

namespace App\Observers;

use App\Models\Calendar;

/**
 * Observer on Calendar Object
 *
 * These methods will be fired by Eloquent base events.
 * - When saved we want to sync the new data for the full Openinghours to the external services
 */
class CalendarObserver
{
    /**
     * process after entitiy is saved
     *
     * trigger jobs of Openinghours
     * that will sync new values to external services
     *
     * @param  Openinghours $openinghours
     * @return void
     */
    public function saved(Calendar $calendar)
    {
        app('VestaService')->makeSyncJobsForExternalServices($calendar->openinghours, 'update');
    }
}
