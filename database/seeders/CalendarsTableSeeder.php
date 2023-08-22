<?php

namespace Database\Seeders;

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
            $openinghour->calendars()->save(Calendar::factory()
                ->make(['published' => true,]));

            $openinghour->calendars()->save(Calendar::factory()
                ->make([
                    'label' => 'Eerste maandag sluitingsdag',
                    'priority' => '-1',
                    'closinghours' => '1',
                    'published' => true,
                ]));

            $openinghour->calendars()->save(Calendar::factory()
                ->make([
                    'label' => 'Zaterdagmorgen open',
                    'priority' => '-2',
                    'closinghours' => '0',
                    'published' => true,
                ]));
        }
        $this->command->info(self::class . " seeded \r");
    }
}
