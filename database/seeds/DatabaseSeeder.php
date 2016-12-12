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
        $admin = User::where('email', 'admin')->first();

        if (empty($admin)) {
            $password = str_random();

            $admin = User::create([
                'name' => 'admin',
                'email' => 'admin',
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
    }
}
