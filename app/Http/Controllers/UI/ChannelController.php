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
use Illuminate\Support\Facades\DB;

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
     * @param StoreChannelRequest $request
     * @param Service $service
     * @return \Illuminate\Http\Response
     */
    public function store(StoreChannelRequest $request, Service $service)
    {
        // The service in the URL is the one the user is authorized for.
        $input = $this->getChannelInput($request);
        $input['service_id'] = $service->id;
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
        $this->assertChannelOfService($service, $channel);

        // A channel can't be moved to another service.
        $input = $this->getChannelInput($request);

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
        $this->assertChannelOfService($service, $channel);

        $channel->delete();

        return response()->json(['Het kanaal werd verwijderd.']);
    }

    /**
     * @param Service $service
     * @param Channel $channel
     * @throws ModelNotFoundException
     */
    private function assertChannelOfService(Service $service, Channel $channel)
    {
        if ((int) $channel->service_id !== (int) $service->id) {
            $exception = new ModelNotFoundException();
            $exception->setModel(Channel::class);

            throw $exception;
        }
    }

    /**
     * Save the order of all channels of a service.
     *
     * The request lists every channel id of the service in the new order;
     * each channel gets its position as weight.
     *
     * @param Request $request
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function order(Request $request, Service $service)
    {
        $request->validate([
            'channels' => 'required|array',
            'channels.*' => 'integer|distinct',
        ]);

        $ids = array_map('intval', $request->input('channels'));
        $serviceIds = $service->channels()->pluck('id')->all();
        sort($serviceIds);
        $sortedIds = $ids;
        sort($sortedIds);
        if ($sortedIds !== $serviceIds) {
            return response()->json(
                ['message' => 'The order must contain every channel of the service exactly once.'],
                422
            );
        }

        // Save through the models, like a regular update, so userstamps are
        // set and the service is touched. Unchanged channels are not saved.
        $channels = $service->channels()->get()->keyBy('id');
        DB::transaction(function () use ($ids, $channels) {
            foreach ($ids as $weight => $id) {
                $channels[$id]->weight = $weight;
                $channels[$id]->save();
            }
        });

        return response()->json($service->channels()->get());
    }

    /**
     * Get the editable channel fields from the request.
     *
     * An empty weight resets the channel to the default order.
     *
     * @param StoreChannelRequest $request
     * @return array
     */
    protected function getChannelInput(StoreChannelRequest $request)
    {
        $input = $request->only(['label', 'type_id', 'weight']);
        if (array_key_exists('weight', $input)) {
            $input['weight'] = (int) $input['weight'];
        }

        return $input;
    }
}
