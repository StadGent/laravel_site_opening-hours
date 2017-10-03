<?php

namespace Database\Seeds;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $services = app()->make('ServicesRepository');

        $sampleServiceNames = [
            'Cultuurdienst',
            'DeCentrale',
            'DepartementCultuur, SportenVrijeTijd',
            'DienstAdministratieveVereenvoudiging',
            'DienstBeleidsparticipatie',
            'DienstBurgerzaken',
            'DienstEvenementen, Feesten, MarktenenForen',
            'DienstInvorderingen',
            'DienstMilieuenKlimaat',
            'DienstMonumentenzorgenArchitectuur',
            'DienstPreventievoorVeiligheid',
            'DienstSamenleven, WelzijnenGezondheid',
            'DienstStedelijkeVernieuwing',
            'DienstWegen',
            'DienstWerkenenOndernemen',
            'IVAHistorischeHuizenGent',
            'Jeugddienst - HuisvandeStudent',
            'MobielDienstencentrum',
            'OOG - OndersteuningspuntOndernemers',
            'Sportdienst - HuisvandeSport',
            'StadsarchiefGent',
        ];

        foreach ($sampleServiceNames as $serviceName) {
            factory(Service::class)
                ->create([
                    'uri' => 'http://dev.foo/' . str_slug($serviceName),
                    'label' => $serviceName,
                ]);
        }
        $this->command->info(self::class . " seeded \r");
    }
}
