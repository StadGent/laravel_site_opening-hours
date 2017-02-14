<?php

namespace App\Jobs;

use App\Repositories\LodServicesRepository;
use App\Repositories\ServicesRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchServices implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The LOD service repository
     *
     * @var App\Repositories\LodServicesRepository
     */
    private $lodServices;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $services = app('ServicesRepository');

        collect((new LodServicesRepository())->fetchServices())->each(function ($service) use ($services) {
            $uniqueProperties = array_only($service, ['uri', 'identifier']);

            try {
                $services->updateOrCreate($uniqueProperties, $service);
            } catch (\Exception $ex) {
                \Log::error('An error occured while upserting services: ' . $ex->getMessage());
                \Log::error($ex->getTraceAsString());
            }
        });
    }
}
