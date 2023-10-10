<?php

namespace Database\Seeders;

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
                'name' => 'Editor',
                'display_name' => 'Redacteur',
                'description' => 'Redacteur van alle diensten',
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
            $role = Role::factory()->create([
                'name' => $roleConfig['name'],
                'display_name' => $roleConfig['display_name'],
                'description' => $roleConfig['description'],
            ]);
        }
        $this->command->info(self::class . " seeded \r");
    }
}
