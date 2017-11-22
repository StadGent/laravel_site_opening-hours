<?php

namespace Database\Seeds;

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
            'Balie',
            'Tele-service',
            'Web-service',
            'Technical staff',
            'Non-public contact',
        ];

        $services = Service::all();
        foreach ($services as $service) {
            shuffle($sampleChannels);
            $tmpChannels = array_slice($sampleChannels, 0, rand(2, 4));
            foreach ($tmpChannels as $newChannel) {
                $service->channels()->save(
                    factory(Channel::class)
                        ->make([
                            'service_id' => $service,
                            'label' => $newChannel,
                        ])
                );
            }
        }
        $this->command->info(self::class . " seeded \r");
    }
}
