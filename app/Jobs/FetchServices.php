<?php

namespace App\Jobs;

use App\Repositories\LodServicesRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;

class FetchServices extends BaseJob implements ShouldQueue
{

    /**
     * The LOD service repository
     *
     * @var App\Repositories\LodServicesRepository
     */
    private $lodServices;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $services = app('ServicesRepository');

        $types = ['vesta', 'recreatex'];

        foreach ($types as $type) {
            collect((new LodServicesRepository())->fetchServices($type))->each(function ($service) use ($services, $type) {
                $uniqueProperties = Arr::only($service, ['uri', 'identifier']);

                $service['source'] = $type;

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
