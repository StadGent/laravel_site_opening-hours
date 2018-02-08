<?php

namespace App\Console\Commands;


use App\Models\Service;

class PrintVestaOutput extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:print-vesta-output {service-id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print the vesta output for development purposes.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $serviceId = $this->argument('service-id');
        $service  = Service::find($serviceId);
        $recurringOHService = App('RecurringOHService');
        $output = $recurringOHService->getRecurringOHForService($service);
        echo $output.PHP_EOL;
    }
}
