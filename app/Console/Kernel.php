<?php

namespace App\Console;

use App\Console\Commands\FetchODHolidays;
use App\Console\Commands\FetchRecreatex;
use App\Console\Commands\FetchServices;
use App\Console\Commands\PrintVestaOutput;
use App\Console\Commands\UpdateSchedulesInVesta;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        FetchODHolidays::class,
        FetchServices::class,
        FetchRecreatex::class,
        UpdateSchedulesInVesta::class,
        PrintVestaOutput::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('openinghours:fetch-services')->weekly();
        //$schedule->command('openinghours:update-vesta')->weekly()->mondays()->at('02:00');
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
