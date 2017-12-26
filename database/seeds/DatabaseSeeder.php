<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * tables to be truncates
     * @var array
     */
    protected $tables = [
        'events',
        'calendars',
        'openinghours',
        'channels',
        'services',
        'user_service_role',
        'role_user',
        'users',
        'roles',
    ];

    /**
     * List of TableSeeder classes for testData
     * @var array
     */
    protected $testDataSeeders = [
        \Database\Seeds\ServicesTableSeeder::class,
        \Database\Seeds\ChannelsTableSeeder::class,
        \Database\Seeds\OpeninghoursTableSeeder::class,
        \Database\Seeds\CalendarsTableSeeder::class,
        \Database\Seeds\EventsTableSeeder::class,
        \Database\Seeds\RolesTableSeeder::class,
        \Database\Seeds\DummyUsersTableSeeder::class,
    ];

    /**
     * List of TableSeeder classes for production
     * @var array
     */
    protected $productionDataSeeders = [
        \Database\Seeds\RolesTableSeeder::class,
        \Database\Seeds\UsersTableSeeder::class,
    ];

    /**
     * Run the database seeds.
     * clean out old values
     * and fill up again
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();
        $this->cleanDatabase();

        $this->command->info("Start Seeding \r");
        $this->command->info("------------- \r");

        $seeders = $this->testDataSeeders;
        if (env('APP_ENV') === 'production') {
            $seeders = $this->productionDataSeeders;
        }

        foreach ($seeders as $seedClass) {
            $this->call($seedClass);
        }

        DB::table('queued_jobs')->truncate();
    }

    /**
     * Truncate the given tables
     */
    private function cleanDatabase()
    {
        $this->command->info("Start clearing database \r");
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->tables as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("Database cleared \r\n");
    }
}
