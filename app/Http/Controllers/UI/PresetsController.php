<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Models\DefaultEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PresetsController extends Controller
{
    /**
     * output cache
     * @var array
     */
    private $output;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all entities
     *
     * Display an extended listing of the resource for the ui.
     * YES I KNOW there are queries in this controller (bite me)
     * I am NOT gonna write a service just for this endpoint
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // validate
        $this->validate($request, [
            'start_date' => 'date|required',
            'end_date' => 'date|required',
        ]);
        // init variables
        $startPeriode = new Carbon($request->input('start_date'));
        $endPeriode = new Carbon($request->input('end_date'));
        $this->output = [];
        // handle some data
        $this->getFrequenties($startPeriode);
        $this->getHolidays($startPeriode, $endPeriode);

        return response()->json($this->output);
    }

    /**
     * handle the yearly frequenties
     * @param Carbon $startPeriode
     *
     * @return void
     */
    private function getFrequenties(Carbon $startPeriode)
    {
        DefaultEvent::where('rrule', 'FREQ=YEARLY')->get()
            ->each(function ($event) use ($startPeriode) {
                $obj = new \stdClass();
                // set start from FREQ not too far from start periode
                $tmpStart = new Carbon($event->start_date);
                if ($tmpStart->year < $startPeriode->year) {
                    $tmpStart->year = $startPeriode->year;
                }
                // but it must be before the request periode or the front end will act strange
                $tmpStart->subYear();
                $obj->start_date = $tmpStart->format('Y-m-d');
                $obj->rrule = $event->rrule;
                $obj->label = $event->label;

                if (!isset($this->output['recurring'])) {
                    $this->output['recurring'] = [];
                }
                $this->output['recurring'][] = $obj;
            });
    }

    /**
     * handle the holiday periodes
     * @param Carbon $startPeriode
     * @param Carbon $endPeriode
     *
     * @return void
     */
    private function getHolidays(Carbon $startPeriode, Carbon $endPeriode)
    {
        DefaultEvent::whereRaw("start_date <= '" . $endPeriode->toDateString() . "' " .
            "AND end_date >= '" . $startPeriode->toDateString() . "'")->get()
            ->each(function (DefaultEvent $event) use ($startPeriode, $endPeriode) {
                $obj = new \stdClass();
                // limit the holidays periode to the begin of the requested periode
                $tmpStart = new Carbon($event->start_date);
                if ($startPeriode > $tmpStart) {
                    $tmpStart = $startPeriode->copy()->subYear(1);
                }
                $obj->start_date = $tmpStart->format('Y-m-d');
                // limit the holidays periode to the end of the requested periode
                $tmpEnd = new Carbon($event->end_date);
                if ($tmpEnd > $endPeriode) {
                    $tmpEnd = $endPeriode->copy();
                }
                $obj->ended = $tmpEnd->format('Y-m-d');
                $obj->label = $event->label;

                if (!isset($this->output['unique'])) {
                    $this->output['unique'] = [];
                }
                if (!isset($this->output['unique'][$tmpEnd->year])) {
                    $this->output['unique'][$tmpEnd->year] = [];
                }
                $this->output['unique'][$tmpEnd->year][] = $obj;
            });
    }
}
