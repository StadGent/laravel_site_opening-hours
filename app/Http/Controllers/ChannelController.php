<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Service;

class ChannelController extends Controller
{
    /**
     * Get with id
     *
     * Base get and return the Channel
     *
     * @return \App\Models\Channel
     */
    public function show(Channel $channel)
    {
        return $channel;
    }

    /**
     * Get Subset of channels from Service
     *
     * @param Service $service
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getFromService(Service $service)
    {
        return $service->channels;
    }
}
