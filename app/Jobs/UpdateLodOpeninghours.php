<?php

namespace App\Jobs;

use App\Repositories\ChannelRepository;
use App\Repositories\LodOpeninghoursRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;
use EasyRdf_Graph as Graph;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLodOpeninghours implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $serviceId;

    /**
     * @var int
     */
    protected $openinghoursId;

    /**
     * Create a new job instance.
     *
     * @param int $serviceId
     * @param int $openinghoursId
     *
     * @return void
     */
    public function __construct($serviceId, $openinghoursId)
    {
        $this->serviceId = $serviceId;

        $this->openinghoursId = $openinghoursId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ServicesRepository::class)->getById($this->serviceId);

        $openinghoursResource = app(OpeninghoursRepository::class)->getOpeninghoursGraph($this->openinghoursId);

        // Get the channel of the openinghours model
        $channel = app(ChannelRepository::class)->getByOpeninghoursId($this->openinghoursId);

        // Add the service and the openinghours' channel to the graph
        $graph = $this->createServiceResource($service, $channel, $openinghoursResource);

        app(LodOpeninghoursRepository::class)->write($service, $channel, $graph);
    }

    /**
     * Return an EasyRdf_Resource based on the service, channel and openinghours
     *
     * @param  array            $service
     * @param  array            $channel
     * @param  EasyRdf_Resource $openinghoursResource
     * @return EasyRdf_Resource
     */
    private function createServiceResource($service, $channel, $openinghoursResource)
    {
        \EasyRdf_Namespace::set('foo', 'http://foo.bar#');

        $graph = $openinghoursResource->getGraph();

        $service = $graph->resource(
            env('BASE_URI') . '/service/' . $service['id'],
            'foo:Service'
        );

        $channel = $graph->resource(
            env('BASE_URI') . '/channel/' . $channel['id'],
            'foo:Channel'
        );

        $service->addResource('foo:channel', $channel);
        $channel->addResource('oh:openinghours', $openinghoursResource);

        return $service;
    }
}
