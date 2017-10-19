<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChannelRequest;
use App\Models\Service;
use App\Repositories\ChannelRepository;
use App\Repositories\ServicesRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    /**
     * @param ChannelRepository $channels
     */
    public function __construct(ChannelRepository $channels)
    {
        $this->channels = $channels;
    }

    /**
     * Get all entities
     *
     * @throws UnexpectedValueException
     */
    public function index()
    {
        throw new UnexpectedValueException('Not yet implemented');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws UnexpectedValueException
     */
    public function create()
    {
        throw new UnexpectedValueException('Not yet implemented');
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

        if (!empty($channel)) {
            return response()->json($channel);
        }

        return response()->json(
            ['message' => 'Something went wrong while storing the new channel, check the logs.'],
            400
        );
    }

    /**
     * Display the specified resource.
     *
     * @todo someone pls check or this has any functionality
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $start = Carbon::today();
        $end = Carbon::today();

        return [
            'label' => 'telefonisch',
            'openinghours' => [
                [
                    'active' => true,
                    'start_date' => $start->subMonth()->toDateString(),
                    'end_date' => $end->subMonth()->addYear()->toDateString(),
                    'id' => 5,
                ],
            ],
        ];
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
     * Show the form for editing the specified resource.
     *
     * @throws UnexpectedValueException
     */
    public function edit()
    {
        throw new UnexpectedValueException('Not yet implemented');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->input();

        $success = $this->channels->update($id, $input);

        if ($success) {
            return response()->json($this->channels->getById($id));
        }

        return response()->json(['message' => 'Something went wrong while updating the channel, check the logs.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $channel = $this->channels->getFullObjectById($id);

        if (empty($channel)) {
            return response()->json(['message' => 'Het kanaal werd niet gevonden.'], 400);
        }

        $this->channels->delete($id);

        return response()->json(['Het kanaal werd verwijderd.']);
    }
}
