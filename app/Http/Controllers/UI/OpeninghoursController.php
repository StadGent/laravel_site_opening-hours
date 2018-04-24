<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpeninghoursRequest;
use App\Models\Calendar;
use App\Models\Openinghours;
use App\Repositories\ChannelRepository;
use App\Repositories\OpeninghoursRepository;

class OpeninghoursController extends Controller
{

    /**
     * @param OpeninghoursRepository $openinghours
     */
    public function __construct(OpeninghoursRepository $openinghours)
    {
        $this->middleware('hasRoleInService');
        $this->openinghours = $openinghours;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOpeninghoursRequest $request
     *
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
            return response()->json(['message' => 'Er is een overlapping met een andere versie.'],
                400);
        }

        $input = $request->input();
        $id = $this->openinghours->store($input);

        if ( ! $result = $this->openinghours->getById($id)) {
            return response()->json(
                ['message' => 'Something went wrong while storing the new openingshours, check the logs.'],
                400
            );
        }

        if ($request->originalVersion !== null) {
            // copy all calendars and events from another version
            foreach ($this->openinghours->getById($request->originalVersion)['calendars'] as $calendar) {
                $calendar['openinghours_id'] = $result['id'];
                $new_calendar = Calendar::create($calendar);
                $new_calendar->events()
                    ->saveMany(Calendar::find($calendar['id'])->events);
            }
        }

        return response()->json(Openinghours::find($id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->openinghours->getById($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreOpeninghoursRequest $request
     * @param Openinghours $openinghours
     *
     * @return \Illuminate\Http\Response
     */
    public function update(
        StoreOpeninghoursRequest $request,
        Openinghours $openinghours
    ) {
        // Make sure the hours don't overlap existing openinghours
        $overlap = app(ChannelRepository::class)->hasOpeninghoursForInterval(
            $request->channel_id,
            $request->start_date,
            $request->end_date,
            $openinghours->id
        );

        if ($overlap) {
            return response()->json(['message' => 'Er is een overlapping met een andere versie.'],
                400);
        }

        $input = $request->input();

        if ( ! $openinghours->update($input)) {
            return response()->json(
                ['message' => 'Something went wrong while updating the openinghours, check the logs.'],
                400
            );
        }

        return response()->json($this->openinghours->getById($openinghours->id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Openinghours $openinghours
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Openinghours $openinghours)
    {
        if ( ! $openinghours->delete()) {
            return response()->json(
                ['message' => 'De openingsuren werden niet verwijderd, er is iets foutgegaan.'],
                400
            );
        }

        return response()->json(['message' => 'De openingsuren werden verwijderd']);
    }
}
