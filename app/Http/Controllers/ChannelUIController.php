<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChannelRequest;
use App\Models\Service;
use App\Repositories\ChannelRepository;
use App\Repositories\ServicesRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChannelUIController extends Controller
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
     * Will not be allowed as this is WAY TOO MUCH data
     * the channels need to be required by the getFromService method
     * Base not implemnted reply
     *
     * @return \Illuminate\Http\Response 501
     */
    public function index()
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return response()->json('Not Implemented', 501);
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

        return response()->json(['message' => 'Something went wrong while storing the new channel, check the logs.'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
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
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json('Not Implemented', 501);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
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
     * @param  int                       $id
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
