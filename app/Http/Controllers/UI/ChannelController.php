<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChannelRequest;
use App\Models\Channel;
use App\Models\Service;
use App\Repositories\ChannelRepository;
use App\Repositories\ServicesRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    /**
     * @param ChannelRepository $channels
     */
    public function __construct(ChannelRepository $channels)
    {
        $this->middleware('hasRoleInService')->except('destroy');
        $this->middleware('hasRoleInService:true')->only('destroy');
        $this->channels = $channels;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreChannelRequest $request)
    {
        $input = $request->input();
        $id = $this->channels->store($input);
        $channel = $this->channels->getById($id);
        if (empty($channel)) {
            return response()->json(
                ['message' => 'Something went wrong while storing the new channel, check the logs.'],
                400
            );
        }

        return response()->json($channel);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreChannelRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function update(StoreChannelRequest $request, Service $service, Channel $channel)
    {
        $input = $request->input();

        if (!$channel->update($input)) {
            return response()->json(
                ['message' => 'Something went wrong while updating the channel, check the logs.'],
                400
            );
        }

        return response()->json(Channel::find($channel->id));
    }

    /**
     * Get subset of Channels from Serivce
     * @param $id
     */
    public function getFromService($id)
    {
        $serviceRepo = new ServicesRepository(Service::find($id));
        $channels = $serviceRepo->getChannels();

        return response()->json($channels);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Service $service
     * @param  Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service, Channel $channel)
    {
        if (!$service->channels->find($channel)) {
            $exception = new ModelNotFoundException();
            $exception->setModel(Channel::class);

            throw $exception;
        }

        $channel->delete();

        return response()->json(['Het kanaal werd verwijderd.']);
    }
}
