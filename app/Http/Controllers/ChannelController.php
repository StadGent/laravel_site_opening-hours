<?php

namespace App\Http\Controllers;

use App\Http\Transformers\ChannelTransformer;
use App\Models\Channel;
use App\Models\Service;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChannelController extends Controller
{
    /**
     * Get with id
     * Base get and return the Channel
     *
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function show(Service $service, Channel $channel)
    {
        if($service->draft){
            $exception = new ModelNotFoundException();
            $exception->setModel(Service::class);
            throw $exception;
        }

        if (!$service->channels->find($channel)) {
            $message = "The requested channel is not a child of the service in the path";
            $exception = new ModelNotFoundException($message);
            $exception->setModel(Channel::class);

            throw $exception;
        }

        return response()->item(new ChannelTransformer(), $channel);
    }

    /**
     * Get Subset of channels from Service
     *
     * @param Service $service
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function getFromService(Service $service, Request $request)
    {
        if ($service->draft) {
            $exception = new ModelNotFoundException();
            $exception->setModel(Service::class);
            throw $exception;
        }

        if ($request['type']) {
            if ($request['type'] === 'null') {
                return response()->collection(new ChannelTransformer(),
                    $service->channels->where('type_id', null));
            }
            return response()->collection(new ChannelTransformer(),
                $service->channels->where('type_id', $request['type']));
        }

        return response()->collection(new ChannelTransformer(),
            $service->channels);
    }
}
