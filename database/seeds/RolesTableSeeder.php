<?php

namespace Database\Seeds;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
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
                'description' => 'The admin of the application, can basically do anything.',
            ],
            [
                'name' => 'AppUser',
                'display_name' => 'Applicatie gebruiker',
                'description' => 'Een gebruiker van de applicatie',
            ],
            [
                'name' => 'Owner',
                'display_name' => 'Beheerder van een dienst',
                'description' => 'Beheerder van een dienst',
            ],
            [
                'name' => 'Member',
                'display_name' => 'Lid van een dienst',
                'description' => 'Lid van een dienst',
            ],
        ];

        foreach ($roles as $roleConfig) {
            $role = Role::create([
                'name' => $roleConfig['name'],
                'display_name' => $roleConfig['display_name'],
                'description' => $roleConfig['description'],
            ]);

            $role->save();
        }
        $this->command->info(self::class . " seeded \r");
    }
}
