<?php

namespace Database\Seeds;

use App\Models\Event;
use App\Models\Openinghours;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dt = new Carbon('2017-01-01');
        $dt->minute = 00;
        $dt->second = 00;

        $start = $dt->copy();
        $end = $dt->copy();

        $openinghours = Openinghours::all();
        foreach ($openinghours as $openinghour) {
            $calendars = $openinghour->calendars;
            $baseCalendar = $calendars->shift();
            // morning hours
            $start->hour = 9;
            $end->hour = 12;
            $baseCalendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => $start,
                        'end_date' => $end,
                    ]));
            // afternoon hours
            $start->hour = 13;
            $end->hour = 18;

            $baseCalendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => $start,
                        'end_date' => $end,
                    ]));

            $exceptionCalendar = $calendars->shift();

            $start->hour = 9;
            $end->hour = 18;
            $exceptionCalendar->events()->save(factory(Event::class)
                    ->make([
                        'rrule' => 'BYSETPOS=1;BYDAY=MO;FREQ=MONTHLY',
                        'start_date' => $start,
                        'end_date' => $end,
                    ]));
        }
        $this->command->info(self::class . " seeded \r");
    }
}
