<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::where('email', 'admin@foo.bar')->first();
        if (empty($admin)) {
            $password = Str::random();
            $admin = User::create([
                'name' => 'admin',
                'email' => 'admin@foo.bar',
                'password' => Hash::make($password),
            ]);
            $admin->save();
            $admin->attachRole(Role::where('name', 'Admin')->first());
            $this->setFooter($password);
        }
    }

    /**
     * Some nice candy for the eye footer
     * With a WARNING !!!
     */
    private function setFooter($password)
    {
        $this->command->info("-------------------------------------------------------------------\r");
        $this->command->info("|                        !!! IMPORTANT !!!                        |\r");
        $this->command->info("-------------------------------------------------------------------\r");
        $this->command->info(" The admin has been create, the random password is: " . $password . "\r");
        $this->command->info(" Copy this into your password manager, this will not be shown again. \r");
        $this->command->info("-------------------------------------------------------------------\r");
        $this->command->info("|                        !!! IMPORTANT !!!                        |\r");
        $this->command->info("-------------------------------------------------------------------\r");
        $this->command->info(self::class . " seeded \r");
    }
}
