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
    protected $supportFormat = 'application/ld+json';
    /**
     * contains the uri of the active record service
     * @var string
     */
    public $service;
    /**
     * Return a JSON-LD formatted openinghours schedule
     * TODO: rework how a schedule is returned, some formats
     * need more basic info of the openinghours instead of
     * formatted hours per day, such as this one.
     *
     * @param Illuminate\Database\Eloquent\Model $data
     * @return $this
     */
    public function render($data)
    {
        if (!$this->service) {
            throw new \Exception("JSON-LD formatter needs a service to be set", 1);
        }
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph = new \EasyRdf_Graph();
        $service = $graph->resource($this->service->uri, 'schema:Organization');
        // get a raw render for the week:
        // $channel id + days index in english
        // for each channel create an openinghours specification
        // where the channel URI is also set as some sort of context
        foreach ($data as $channelObj) {
            $channelSpecification = $graph->resource(createChannelUri($channelObj->channelId), 'cv:Channel');
            $channelSpecification->addLiteral('schema:label', $channelObj->channel);
            if (isset($channelObj->openNow)) {
                $channelSpecification->addLiteral('schema:isOpenNow', ($channelObj->openNow) ? 'true' : 'false');
            } else {
                $channelSpecification->addLiteral('schema:openingHours', $this->makeTextForDayInfo($channelObj->openinghours));
            }
            $channelSpecification->addResource('cv:isOwnedBy', $service);
        }
        $serialiser = new JsonLdSerialiser();
        $this->output = $serialiser->serialise($graph, 'jsonld');

        return $this;
    }
}
