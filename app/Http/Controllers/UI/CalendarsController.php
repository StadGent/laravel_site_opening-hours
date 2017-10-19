<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteCalendarRequest;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Repositories\CalendarRepository;

class CalendarsController extends Controller
{
    /**
     * @param CalendarRepository $calendars
     */
    public function __construct(CalendarRepository $calendars)
    {
        $this->calendars = $calendars;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        throw new Exception('Not yet implemented');
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
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCalendarRequest $request)
    {
        $input = $request->input();

        $id = $this->calendars->store($input);

        // If events are passed, bulk insert them
        if (!empty($input['events']) && !empty($id)) {
            $this->bulkInsert($id, $input['events']);
        }

        if (!empty($id)) {
            $calendar = $this->calendars->getById($id);

            return response()->json($calendar);
        }

        return response()->json(['message' => 'Something went wrong while storing the new channel, check the logs.'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->calendars->getById($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        throw new Exception('Not yet implemented');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCalendarRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCalendarRequest $request, $id)
    {
        $input = $request->input();

        // If events are passed, bulk insert them
        if (!empty($input['events'])) {
            $this->bulkInsert($id, $input['events']);
        }
        /*
         * update object AFTER bulk insert
         * to get all data correct for event on observer
         */
        $success = $this->calendars->update($id, $input);
        if ($success) {
            return response()->json($this->calendars->getById($id));
        }

        return response()->json(['message' => 'Something went wrong while updating the calendar, check the logs.'], 400);
    }

    /**
     * Bulk insert events
     *
     * @param integer $id
     * @param array $events
     * @return void
     */
    private function bulkInsert($calendarId, $events)
    {
        // Make sure the calendar_id is passed with the event
        // so it gets linked properly
        array_walk($events, function (&$event) use ($calendarId) {
            $event['calendar_id'] = $calendarId;
        });

        // Detach the current events from the calendar, then bulk insert them
        app('EventRepository')->deleteForCalendar($calendarId);

        return app('EventRepository')->bulkInsert($events);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DeleteCalendarRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteCalendarRequest $request, $calendarId)
    {
        $calendar = $this->calendars->getById($calendarId);

        if (empty($calendar)) {
            return response()->json(['message' => 'De kalender werd niet verwijderd, er is iets foutgegaan.'], 400);
        }

        $success = $this->calendars->delete($calendarId);

        if ($success) {
            return response()->json(['message' => 'De kalender werd verwijderd.']);
        }

        return response()->json(['message' => 'De kalender werd niet verwijderd, er is iets foutgegaan.'], 400);
    }
}
