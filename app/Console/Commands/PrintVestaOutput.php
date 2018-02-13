<?php

namespace App\Console\Commands;


use App\Models\Service;
use Carbon\Carbon;

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
        $service = Service::find($serviceId);
        $recurringOHService = App('RecurringOHService');
        $startDate = Carbon::today()->startOfWeek();
        $endDate = $startDate->copy()->addMonths(3);
        $output = $recurringOHService->getServiceOutput($service, $startDate, $endDate);
        echo $output . PHP_EOL;
    }
}
