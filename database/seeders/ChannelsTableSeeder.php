<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ChannelsTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sampleChannels = [
            [
                'label' => 'Balie',
                'type' => 2,
            ],
            [
                'label' => 'Tele-service',
                'type' => 3,
            ],
            [
                'label' => 'Op afspraak',
                'type' => 1,
            ],
        ];

        $services = Service::all();
        foreach ($services as $service) {
            foreach ($sampleChannels as $newChannel) {
                $service->channels()->save(
                    factory(Channel::class)
                        ->make([
                            'service_id' => $service,
                            'label' => $newChannel['label'],
                            'type_id' => $newChannel['type'],
                        ])
                );
            }
        }
        $this->command->info(self::class . " seeded \r");
    }
}
