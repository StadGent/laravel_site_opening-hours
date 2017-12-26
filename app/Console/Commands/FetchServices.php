<?php

namespace App\Console\Commands;

use App\Jobs\FetchServices as FetchServicesJob;

class FetchServices extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openinghours:fetch-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the available public services from an LOD endpoint.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        dispatch(new FetchServicesJob());

        $this->info('Dispatched a job that will fetch the services from the SPARQL endpoint.');
    }
}
