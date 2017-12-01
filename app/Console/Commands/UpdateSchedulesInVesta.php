<?php

namespace App\Console\Commands;

use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Service;

/**
 * Command to run every week to refresh the data on VESTA
 * Data of 3 months and update will be hanlded in the JOB per service
 *
 * The JOB will also be triggered by alterations on the models by the Observers
 *
 * This command makes sure that the data is up to date for the next 3 months
 * when no updates where triggerd by the observers
 */
class UpdateSchedulesInVesta extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:update-vesta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the output of all VESTA related services to VESTA. ' .
        'The output will be the week schedule starting on monday of the week in which the timestamp ' .
        'of the execution of the command falls in.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $checkedServices = 0;
        $this->info('Init UpdateSchedulesInVesta');
        $services = Service::where('source', 'vesta')->where('draft', 0)
            ->get();

        foreach ($services as $service) {
            $this->info('Dispatch a job that will update the services (' . $service->id . ') ' .
                $service->label . ' to VESTA');
            dispatch((new UpdateVestaOpeninghours($service->identifier, $service->id)));
            $checkedServices++;
        }

        $this->info('Nr of services set in queue: ' . $checkedServices);
        $this->info('Done UpdateSchedulesInVesta');
    }
}
