<?php

namespace App\Observers;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Openinghours;
use App\Models\Service;

class OpeninghoursObserver
{
    /**
     * process after entitiy is saved
     *
     * check or vesta needs to be updated for this openinghours
     * updated openinghours that is stored in LOD
     *
     * @param  Openinghours $openinghours
     * @return void
     */
    public function saved(Openinghours $openinghours)
    {
        $channel = $openinghours->channel;
        $service = $channel->service;

        $this->updateVestaWithOpeninghours($openinghours, $service);
        dispatch(new UpdateLodOpeninghours($service->id, $openinghours->id, $channel->id));
    }

    /**
     * process before entitiy is removed
     *
     * check or vesta needs to be updated for this openinghours
     * deleting openinghours that is stored in LOD
     */
    public function deleting(Openinghours $openinghours)
    {
        $service = $openinghours->channel->service;

        $this->updateVestaWithOpeninghours($openinghours, $service);
        dispatch(new DeleteLodOpeninghours($service->id, $openinghours->id));
    }

    /**
     * Update Vesta With the Active Openinghours of the veste source
     *
     * update the VESTA openinghours of the service,
     * ONLY WHEN that service is linked to a VESTA UID
     *
     * @param  Openinghours $openinghours
     * @param  Service      $service
     */
    private function updateVestaWithOpeninghours(Openinghours $openinghours, Service $service)
    {
        if ($openinghours->active) {
            if (!empty($service) && $service->source == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service->identifier, $service->id)));
            }
        }
    }
}
