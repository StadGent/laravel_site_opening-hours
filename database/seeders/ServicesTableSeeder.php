<?php

namespace Database\Seeders;

use App\Jobs\FetchServices;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServicesTableSeeder extends Seeder
{
    /**
     * trigger the FetchServices job
     * @var boolean
     */
    protected $getExternal = false;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if ($this->getExternal) {
            $job = new FetchServices();
            $job->handle();
            $this->command->info(self::class . " seeded with external data\r");

            return;
        }

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
                    'uri' => 'http://dev.foo/' . Str::slug($serviceName),
                    'label' => $serviceName,
                ]);
        }
        $this->command->info(self::class . " seeded \r");
    }
}
