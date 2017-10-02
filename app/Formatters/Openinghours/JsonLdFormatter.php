<?php

namespace App\Formatters\Openinghours;

use EasyRdf_Serialiser_JsonLd as JsonLdSerialiser;

/**
 * Json-Ld Formatter class for Openinghours
 * renders given objects into json-ld
 */
class JsonLdFormatter extends BaseFormatter
{
    /**
     * @var string
     */
    protected $supportFormat = 'json-ld';
    /**
     * contains the uri of the active record service
     * @var string
     */
    public $serviceUri;
    /**
     * Return a JSON-LD formatted openinghours schedule
     * TODO: rework how a schedule is returned, some formats
     * need more basic info of the openinghours instead of
     * formatted hours per day, such as this one.
     *
     * @param Illuminate\Database\Eloquent\Model $data
     * @return json-ld
     */
    public function render($data)
    {
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph = new \EasyRdf_Graph();
        $service = $graph->resource($this->serviceUri, 'schema:Organization');
        $serviceModel = \App\Models\Service::where('uri', '=', $this->serviceUri)->first();
        $channels = $serviceModel->channels;
        // get a raw render for the week:
        // $channel id + days index in english
        // for each channel create an openinghours specification
        // where the channel URI is also set as some sort of context
        foreach ($data as $channelName => $schedule) {
            $channel = $channels->where('label', '=', $channelName)->first();
            $channelSpecification = $graph->resource(createChannelUri($channel->id), 'cv:Channel');
            $channelSpecification->addLiteral('schema:label', $channelName);
            $channelSpecification->addLiteral('schema:openingHours', $this->makeTextForDayInfo($schedule));
            $channelSpecification->addResource('cv:isOwnedBy', $service);
        }
        $serialiser = new JsonLdSerialiser();
        $this->output = $serialiser->serialise($graph, 'jsonld');

        return $this;
    }
}
