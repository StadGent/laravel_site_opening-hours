<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Models\Calendar;
use App\Repositories\CalendarRepository;

class CalendarsController extends Controller
{
    /**
     * @param CalendarRepository $calendars
     */
    public function __construct(CalendarRepository $calendars)
    {
        $this->middleware('hasRoleInService');
        $this->calendars = $calendars;
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
        if (!$id = $this->calendars->store($input)) {
            return response()->json(
                ['message' => 'Something went wrong while storing the new channel, check the logs.'],
                400
            );
        }

        // If events are passed, bulk insert them
        if (!empty($input['events']) && !empty($id)) {
            $this->bulkInsert($id, $input['events']);
        }
        $calendar = $this->calendars->getById($id);

        return response()->json($calendar);
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
        if (!$this->calendars->update($id, $input)) {
            return response()->json(
                ['message' => 'Something went wrong while updating the calendar, check the logs.'],
                400
            );
        }

        return response()->json($this->calendars->getById($id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Calendar $calendar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        if ($calendar['priority'] === 0) {
            return response()->json(
                ['message' => 'De basiskalender kan je niet verwijderen.'],
                400
            );
        }

        if (!$this->calendars->delete($calendar->id)) {
            return response()->json(['message' => 'De kalender werd niet verwijderd, er is iets foutgegaan.'], 400);
        }

        return response()->json(['message' => 'De kalender werd verwijderd.']);
    }

    /**
     * Bulk insert events
     *
     * @param $calendarId
     * @param $events
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
}
