<?php

namespace App\Http\Controllers;

use App\Events\OpeninghoursUpdated;
use App\Http\Requests\DeleteOpeninghoursRequest;
use App\Http\Requests\StoreOpeninghoursRequest;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ChannelRepository;
use App\Events\OpeninghoursDeleted;

class OpeninghoursController extends Controller
{
    public function __construct(OpeninghoursRepository $openinghours)
    {
        $this->middleware('auth');

        $this->openinghours = $openinghours;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOpeninghoursRequest $request)
    {
        // Make sure the hours don't overlap existing openinghours
        $overlap = app(ChannelRepository::class)->hasOpeninghoursForInterval(
            $request->channel_id,
            $request->start_date,
            $request->end_date
        );

        if ($overlap) {
            return response()->json(['message' => 'Er is een overlapping met een andere versie.'], 400);
        }

        $input = $request->input();

        $id = $this->openinghours->store($input);

        $openinghours = $this->openinghours->getById($id);

        if (! empty($openinghours)) {
            event(new OpeninghoursUpdated($id));

            return response()->json($openinghours);
        }

        return response()->json(['message' => 'Something went wrong while storing the new openingshours, check the logs.'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->openinghours->getById($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreOpeninghoursRequest $request, $id)
    {
        // Make sure the hours don't overlap existing openinghours
        $overlap = app(ChannelRepository::class)->hasOpeninghoursForInterval(
            $request->channel_id,
            $request->start_date,
            $request->end_date,
            $id
        );

        if ($overlap) {
            return response()->json(['message' => 'Er is een overlapping met een andere versie.'], 400);
        }

        $input = $request->input();

        $success = $this->openinghours->update($id, $input);

        if ($success) {
            event(new OpeninghoursUpdated($id));

            return response()->json($this->openinghours->getById($id));
        }

        return response()->json(['message' => 'Something went wrong while updating the openinghours, check the logs.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteOpeninghoursRequest $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteOpeninghoursRequest $request, $id)
    {
        $openinghours = $this->openinghours->getFullObjectById($id);

        $success = $this->openinghours->delete($id);

        if ($success) {
            event(new OpeninghoursDeleted($openinghours, $this->openinghours->isOpeninghoursRelevantNow($openinghours)));

            return response()->json(['message' => 'De openingsuren werden verwijderd']);
        }

        return response()->json(['message' => 'De openingsuren werden niet verwijderd, er is iets foutgegaan.'], 400);
    }
}
