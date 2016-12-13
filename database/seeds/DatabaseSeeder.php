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

            $this->command->info('The admin has been created, the random password is: ' . $password . ' Copy this into your password manager, this will not be shown again.');
        }

        // Seed the roles and give the admin the admin role
        $roles = ['Admin', 'AppUser', 'PublicServiceAdmin', 'CalenderUser'];

        foreach ($roles as $roleName => $displayName) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role = Role::create([
                    'name' => $roleName,
                    'display_name' => $roleName,
                ]);
            }
        }

        // Seed dummy services
        $this->seedDummyServices();
    }

    private function seedDummyServices()
    {
        $services = app()->make('ServicesRepository');

        $servicesData = [
            [
                'uri' => 'http://dev.foo/service1',
                'label' => 'Service1',
                'description' => 'Description of the service'
            ],
            [
                'uri' => 'http://dev.foo/service2',
                'label' => 'Service2',
                'description' => 'Description of the service'
            ],
            [
                'uri' => 'http://dev.foo/service3',
                'label' => 'Service3',
                'description' => 'Description of the service'
            ]
        ];

        foreach ($servicesData as $serviceConfig) {
            $service = $services->where('uri', $serviceConfig['uri'])->first();

            if (empty($service)) {
                $services->store($serviceConfig);
            }
        }
    }
}
