<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteOpeninghoursRequest;
use App\Http\Requests\StoreOpeninghoursRequest;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;

class OpeninghoursController extends Controller
{
    /**
     * @param OpeninghoursRepository $openinghours
     */
    public function __construct(OpeninghoursRepository $openinghours)
    {
        $this->openinghours = $openinghours;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOpeninghoursRequest $request
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

        if (!empty($openinghours)) {
            return response()->json($openinghours);
        }

        return response()->json(
            ['message' => 'Something went wrong while storing the new openingshours, check the logs.'],
            400
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->openinghours->getById($id));
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
            return response()->json($this->openinghours->getById($id));
        }

        return response()->json(
            ['message' => 'Something went wrong while updating the openinghours, check the logs.'],
            400
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteOpeninghoursRequest $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteOpeninghoursRequest $request)
    {
        $success = $this->openinghours->delete($request->openinghours->id);

        if ($success) {
            return response()->json(['message' => 'De openingsuren werden verwijderd']);
        }

        return response()->json(['message' => 'De openingsuren werden niet verwijderd, er is iets foutgegaan.'], 400);
    }
}
