<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dt = new Carbon('2017-01-01');
        $dt->minute = 00;
        $dt->second = 00;
        $start = $dt->copy();
        $start->hour = 9;
        $end = $dt->copy();
        $end->hour = 18;
        return [
            'rrule' => 'BYDAY=MO,TU,WE,TH,FR;FREQ=WEEKLY',
            'start_date' => $start,
            'end_date' => $end,
            'label' => 1,
            'until' => $dt->modify('31 December')->addYear(),
        ];
    }
}
