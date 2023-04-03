<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Openinghours;
use Illuminate\Database\Seeder;

class OpeninghoursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $channels = Channel::whereNotNull('id')
            ->get()
            ->values();

        foreach ($channels as $channel) {
            $channel->openinghours()->save(factory(Openinghours::class)->make());
        }

        $this->command->info(self::class . " seeded \r");
    }
}
