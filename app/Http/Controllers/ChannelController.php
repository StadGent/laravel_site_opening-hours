<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChannelController extends Controller
{
    /**
     * Get with id
     *
     * Base get and return the Channel
     *
     * @return \App\Models\Channel
     */
    public function show(Service $service, Channel $channel)
    {
        if (!$service->channels->find($channel)) {
            $message = "The requested channel is not a child of the service in the path";
            $exception = new ModelNotFoundException($message);
            $exception->setModel(Channel::class);

            throw $exception;
        }

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
