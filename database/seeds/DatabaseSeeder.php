<?php

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed the roles and give the admin the admin role
        $roles = [
            [
                'name' => 'Admin',
                'display_name' => 'Admin',
                'description' => 'The admin of the application, can basically do anything.'
            ],
            [
                'name' => 'AppUser',
                'display_name' => 'Applicatie gebruiker',
                'description' => 'Een gebruiker van de applicatie'
            ],
            [
                'name' => 'Owner',
                'display_name' => 'Beheerder van een dienst',
                'description' => 'Beheerder van een dienst'
            ],
            [
                'name' => 'Member',
                'display_name' => 'Lid van een dienst',
                'description' => 'Lid van een dienst'
            ],
        ];

        foreach ($roles as $roleConfig) {
            $role = Role::where('name', $roleConfig)->first();

            if (empty($role)) {
                $role = Role::create([
                    'name' => $roleConfig['name'],
                    'display_name' => $roleConfig['display_name'],
                    'description' => $roleConfig['description']
                ]);

                $role->save();
            }
        }

        // Create an admin user (if not present)
        $admin = User::where('email', 'admin@foo.bar')->first();

        if (empty($admin)) {
            $password = str_random();

            $admin = User::create([
                'name' => 'admin',
                'email' => 'admin@foo.bar',
                'password' => bcrypt($password)
            ]);

            $admin->save();
            $admin->attachRole(Role::where('name', 'Admin')->first());

            $this->command->info('The admin has been created, the random password is: ' . $password . ' Copy this into your password manager, this will not be shown again.');
        }

        // Seed dummy services
        $this->seedDummyServices();
    }

    private function seedDummyServices()
    {
        $services = app()->make('ServicesRepository');

        $sampleServiceNames = [
            'Cultuurdienst',
            'De Centrale',
            'Departement Cultuur, Sport en Vrije Tijd',
            'Dienst Administratieve Vereenvoudiging',
            'Dienst Beleidsparticipatie',
            'Dienst Burgerzaken',
            'Dienst Evenementen, Feesten, Markten en Foren',
            'Dienst Invorderingen',
            'Dienst Milieu en Klimaat',
            'Dienst Monumentenzorg en Architectuur',
            'Dienst Preventie voor Veiligheid',
            'Dienst Samenleven, Welzijn en Gezondheid',
            'Dienst Stedelijke Vernieuwing',
            'Dienst Wegen',
            'Dienst Werken en Ondernemen',
            'IVA Historische Huizen Gent',
            'Jeugddienst - Huis van de Student',
            'Mobiel Dienstencentrum',
            'OOG - Ondersteuningspunt Ondernemers',
            'Sportdienst - Huis van de Sport',
            'Stadsarchief Gent',
        ];

        $servicesData = array_map(function ($name) {
            return [
                'uri' => 'http://dev.foo/' . str_slug($name),
                'label' => $name,
                'description' => '',
            ];
        }, $sampleServiceNames);



        foreach ($servicesData as $serviceConfig) {
            $service = $services->where('uri', $serviceConfig['uri'])->first();

            if (empty($service)) {
                $services->store($serviceConfig);
            }
        }
    }
}
