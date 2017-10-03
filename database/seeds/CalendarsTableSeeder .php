<?php

namespace Database\Seeds;

use App\Models\Calendar;
use App\Models\Openinghours;
use Illuminate\Database\Seeder;

class CalendarsTableSeeder extends Seeder
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
            $openinghour->calendars()->save(factory(Calendar::class)
                    ->make());
        }
        $this->command->info(self::class . " seeded \r");
    }
}
