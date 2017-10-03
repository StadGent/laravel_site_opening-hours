<?php

namespace Database\Seeds;

use App\Models\Calendar;
use App\Models\Event;
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

        $dt = Carbon::now()->modify('01 January');
        $dt->minute = 00;
        $dt->second = 00;

        $start = $dt->copy();
        $end = $dt->copy();

        $calendars = Calendar::all();
        foreach ($calendars as $calendar) {
            // morning hours
            $start->hour = 9;
            $end->hour = 12;
            $calendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => $start,
                        'end_date' => $end,
                    ]));
            // afternoon hours
            $start->hour = 13;
            $end->hour = 18;
            $calendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => $start,
                        'end_date' => $end,
                    ]));
        }
        $this->command->info(self::class . " seeded \r");
    }
}
