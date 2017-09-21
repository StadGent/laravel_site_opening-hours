<?php

namespace App\Observers;

use App\Models\Openinghours;

/**
 * Observer on Openinghours Object
 *
 * These methods will be fired by Eloquent base events.
 * - When saved we want to sync the new data to the external services
 * - Before deleting we want to remove the data from the external services
 */
class OpeninghoursObserver
{
    /**
     * process after entitiy is saved
     *
     * create jobs that will sync new values to external services
     *
     * @param  Openinghours $openinghours
     * @return void
     */
    public function saved(Openinghours $openinghours)
    {
        app('OpeninghoursService')->makeSyncJobsForExternalServices($openinghours, 'update');
    }

    /**
     * process before entitiy is removed
     *
     * create jobs that will remove values from external services
     *
     * @param  Openinghours $openinghours
     * @return void
     */
    public function deleting(Openinghours $openinghours)
    {
        app('OpeninghoursService')->makeSyncJobsForExternalServices($openinghours, 'delete');
    }
}
