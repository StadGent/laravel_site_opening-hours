<?php

namespace App\Repositories;

use App\Services\SparqlService;
use EasyRdf_Serialiser_Turtle as TurtleSerialiser;

/**
 * This class is responsible for writing openinghours to a LOD (SPARQL based) database
 */
class LodOpeninghoursRepository
{
    /**
     * Overwrite the triples of a given service
     *
     * @param  string        $service
     * @param  string        $channel
     * @param  EasyRdf_Graph $graph
     * @return bool
     */
    public function write($service, $channel, $graph)
    {
        $query = $this->makeUpdateSparqlQuery($service, $channel, $graph);

        return $this->makeSparqlService()->performSparqlQuery($query, 'POST');
    }

    /**
     * Delete channel
     *
     * @param int $channelId
     */
    public function deleteChannel($channelId)
    {
        $channelUri = env('BASE_URI') . '/channel/' . $channelId;
        $graph = env('SPARQL_WRITE_GRAPH');

        if (empty($graph)) {
            \Log::warning('No graph was configured, we could not delete the openinghours');

            return false;
        }

        $query = "DELETE WHERE { GRAPH <$graph> {?s ?p ?o. FILTER(?s = <$channelUri>)}}";

        $result = $this->makeSparqlService()->performSparqlQuery($query, 'POST');
    }

    /**
     * Delete openinghours
     *
     * @param  int  $openinghoursId
     * @return bool
     */
    public function deleteOpeninghours($openinghoursId)
    {
        $openinghoursUri = env('BASE_URI') . '/openinghours/' . $openinghoursId;
        $graph = env('SPARQL_WRITE_GRAPH');

        if (empty($graph)) {
            \Log::warning('No graph was configured, we could not delete the openinghours');

            return false;
        }

        $query = "DELETE WHERE { GRAPH <$graph> {?s ?p ?o. FILTER(?s = <$openinghoursUri>)}}";

        $result = $this->makeSparqlService()->performSparqlQuery($query, 'POST');
    }

    /**
     * Make a SPARQL query that writes openinghours information based
     * on a DELETE/INSERT statement
     *
     * @param  string $serviceUri
     * @param  array  $triples
     * @return bool
     */
    private function makeUpdateSparqlQuery($service, $channel, $graph)
    {
        $serviceUri = env('BASE_URI') . '/service/' . $service['id'];
        $channelUri = env('BASE_URI') . '/channel/' . $channel['id'];

        // Get the graph to write too
        $graphName = $this->getGraphName();

        $serialiser = new TurtleSerialiser();

        $triples = $serialiser->serialise($graph->getGraph(), 'turtle');

        list($triples, $headers) = $this->splitHeadersAndTriples($triples);

        $query = "
            $headers
            WITH <$graphName>
            DELETE WHERE { ?s ?p ?o. FILTER(?s = <$channelUri>)}
            INSERT { $triples }
        ";

        return $query;
    }

    /**
     * Split the headers and triples from a textual Turtle representation
     *
     * @param  string $triples
     * @return array
     */
    private function splitHeadersAndTriples($triples)
    {
        preg_match_all('#(^@prefix.*?)\s{1,}\.#m', $triples, $matches);

        $headers = implode(' ', $matches[1]);
        $headers = str_replace('@', '', $headers);

        preg_match_all('#(^[^@].*)#m', $triples, $matches);

        $triples = implode(' ', $matches[1]);

        return [$triples, $headers];
    }

    /**
     * Create and return a SparqlService object
     *
     * @return App\Services\SparqlService
     */
    private function makeSparqlService()
    {
        return new SparqlService(
            env('SPARQL_WRITE_ENDPOINT'),
            env('SPARQL_WRITE_ENDPOINT_USERNAME'),
            env('SPARQL_WRITE_ENDPOINT_PASSWORD')
        );
    }

    /**
     * Return the name of the graph to write too
     * throws an exception if it's not set
     *
     * @return string
     * @throws Exception
     */
    private function getGraphName()
    {
        $graph = env('SPARQL_WRITE_GRAPH');

        if (empty($graph)) {
            throw new \Exception('A graph to write to must be configured.');
        }

        return $graph;
    }
}
