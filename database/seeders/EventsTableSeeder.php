<?php

namespace Database\Seeders;

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
            $baseCalendar->events()->save(Event::factory()
                ->make([
                    'start_date' => '2017-01-01 09:00',
                    'end_date' => '2017-01-01 12:00',
                ]));
            $baseCalendar->events()->save(Event::factory()
                ->make([
                    'start_date' => '2017-01-01 13:00',
                    'end_date' => '2017-01-01 17:00',
                ]));

            $exceptionCalendar = $calendars->shift();
            $exceptionCalendar->events()->save(Event::factory()
                ->make([
                    'rrule' => 'BYSETPOS=1;BYDAY=MO;FREQ=MONTHLY',
                    'start_date' => '2017-01-01 09:00',
                    'end_date' => '2017-01-01 18:00',
                ]));

            $exceptionCalendar = $calendars->shift();
            $exceptionCalendar->events()->save(Event::factory()
                ->make([
                    'rrule' => 'BYDAY=SA;FREQ=WEEKLY;INTERVAL=2',
                    'start_date' => '2017-01-01 10:00',
                    'end_date' => '2017-01-01 12:00',
                ]));
        }
        $this->command->info(self::class . " seeded \r");
    }
}
