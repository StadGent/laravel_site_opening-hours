<?php

namespace App\Jobs;

use App\Models\Openinghours;
use App\Repositories\ChannelRepository;
use App\Repositories\LodOpeninghoursRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;
use EasyRdf_Graph as Graph;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLodOpeninghours extends BaseJob implements ShouldQueue
{
    /**
     * @var int
     */
    protected $serviceId;

    /**
     * @var int
     */
    protected $openinghoursId;

    /**
     * @var int
     */
    protected $channelId;

    /**
     * Create a new job instance.
     *
     * @param int $serviceId
     * @param int $openinghoursId
     * @param int $channelId
     *
     * @return void
     */
    public function __construct($serviceId, $openinghoursId, $channelId)
    {
        parent::__construct();
        $this->serviceId = $serviceId;
        $this->openinghoursId = $openinghoursId;
        $this->channelId = $channelId;

        $this->extModelClass = Openinghours::class;
        $this->extId = $openinghoursId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = app(ServicesRepository::class)->getById($this->serviceId);

        $openinghoursGraph = app(OpeninghoursRepository::class)->getOpeninghoursGraphForChannel($this->channelId);

        // Get the channel of the openinghours model
        $channel = app(ChannelRepository::class)->getByOpeninghoursId($this->openinghoursId);

        // Add the service and the openinghours' channel to the graph
        $graph = $this->createServiceResource($service, $channel, $openinghoursGraph);

        if (!app(LodOpeninghoursRepository::class)->update($service, $channel, $this->openinghoursId, $graph)) {
            $this->letsFail();
            return;
        }
        $this->letsFinish();
    }

    /**
     * Return an EasyRdf_Resource based on the service, channel and openinghours
     *
     * @param  array $service
     * @param  array $channel
     * @param  EasyRdf_Graph $openinghoursGraph
     * @return EasyRdf_Resource
     */
    private function createServiceResource($service, $channel, $openinghoursGraph)
    {
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        //$graph = $openinghoursGraph->getGraph();

        $service = $openinghoursGraph->resource(
            createServiceUri($service['id']),
            'cv:PublicOrganisation'
        );

        $channelId = $channel['id'];

        $channel = $openinghoursGraph->resource(
            createChannelUri($channelId)
        );

        $channel->addResource('cv:isOwnedBy', $service);

        if (!empty(env('DATA_REPRESENTATION_URI'))) {
            $channel->addResource('rdfs:isDefinedBy', env('DATA_REPRESENTATION_URI') . '/channel/' . $channelId);
        }

        return $service;
    }
}
