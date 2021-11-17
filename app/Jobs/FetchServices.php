<?php

namespace App\Jobs;

use App\Repositories\LodServicesRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class FetchServices extends BaseJob implements ShouldQueue
{

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $services = app('ServicesRepository');

        $types = ['vesta', 'recreatex', 'publicToilets'];

        $repository = new LodServicesRepository();

        foreach ($types as $type) {
            collect($repository->fetchServices($type))->each(function ($service) use ($services, $type) {
                $service['source'] = $type;
                $uniqueProperties = Arr::only($service, ['source', 'identifier']);


                try {
                    $services->updateOrCreate($uniqueProperties, $service);
                    $this->letsFinish();
                } catch (\Exception $ex) {
                    $this->letsFail($ex->getMessage());
                }
            });
        }
    }
}
