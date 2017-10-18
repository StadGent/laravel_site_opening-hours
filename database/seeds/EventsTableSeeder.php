<?php

namespace Database\Seeds;

use App\Models\Event;
use App\Models\Openinghours;
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
        $openinghours = Openinghours::all();
        foreach ($openinghours as $openinghour) {
            $calendars = $openinghour->calendars;
            $baseCalendar = $calendars->shift();
            $baseCalendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => '2017-01-01 09:00',
                        'end_date' => '2017-01-01 12:00',
                    ]));

            $baseCalendar->events()->save(factory(Event::class)
                    ->make([
                        'start_date' => '2017-01-01 13:00',
                        'end_date' => '2017-01-01 17:00',
                    ]));

            $exceptionCalendar = $calendars->shift();

            $exceptionCalendar->events()->save(factory(Event::class)
                    ->make([
                        'rrule' => 'BYSETPOS=1;BYDAY=MO;FREQ=MONTHLY',
                        'start_date' => '2017-01-01 09:00',
                        'end_date' => '2017-01-01 18:00',
                    ]));
        }
        $this->command->info(self::class . " seeded \r");
    }
}
