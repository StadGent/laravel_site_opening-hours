<?php

namespace App\Observers;

use App\Models\Service;
use Illuminate\Support\Facades\Log;

/**
 * Observer on Service Object
 *
 * These methods will be fired by Eloquent base events.
 * - When saved we want to sync the new data for the full Openinghours to the external services
 */
class ServiceObserver
{
    /**
     * process after entity is saved
     *
     * trigger jobs of Openinghours
     * that will sync new values to external services
     *
     * @param  Service $service
     * @return void
     */
    public function saved(Service $service)
    {
        Log::info('service observer triggered');
        Log::error('Something is really going wrong.');
        error_log('service observer triggered');
        app('ServiceService')->makeSyncJobsForExternalServices($service, 'update');
    }
}
