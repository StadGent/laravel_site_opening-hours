<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = Role::all();

        foreach ($roles as $roleConfig) {
            $name = strtolower($roleConfig['name']);
            $password = 'opening' . $name;

            $user = User::factory()->create([
                'name' => $name . 'user',
                'email' => $name . '@foo.bar',
                'password' => Hash::make($password),
            ]);

            $user->save();

            if ($name === 'admin' || $name === 'editor') {
                $user->addRole($roleConfig);
            } else {
                \DB::insert(
                    'INSERT INTO user_service_role (user_id, role_id, service_id) VALUES (?, ?, ?)',
                    [$user->id, $roleConfig->id, 1]
                );
            }

            $this->command->info("* The '" . $name . "' user has been created, \r");
            $this->command->info("  his stupid password is: '" . $password . "'\r\n");
        }
        $this->setFooter();
    }

    /**
     * Some nice candy for the eye footer
     * With a WARNING !!!
     */
    private function setFooter()
    {
        $this->command->info("----------------------------------------------------------------\r");
        $this->command->info("| These STUPID and UNSAVE users are for testing purpusses only |\r");
        $this->command->info("|           NEVER EVER use this seed in production !!!         |\r");
        $this->command->info("----------------------------------------------------------------\r");
        $this->command->info(self::class . " seeded \r");
    }
}
