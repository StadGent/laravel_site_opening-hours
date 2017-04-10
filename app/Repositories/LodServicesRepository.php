<?php

namespace App\Repositories;

use App\Services\SparqlService;
use EasyRdf_Graph as Graph;
use EasyRdf_Parser_Turtle as Parser;

/**
 * This class is responsible for fetching services from a SPARQL endpoint
 */
class LodServicesRepository
{
    /**
     * Return the services fetched from the SPARQL endpoint
     * in a structure that is compatible with our internal Service model
     *
     * @return array
     */
    public function fetchServices()
    {
        $limit = 100;
        $page = 0;

        $data = [];

        $graphData = $this->makeSparqlService()->performSparqlQuery($this->getServicesQuery($limit, 0));

        // Transform the data in a compatible format
        $data = $this->transform($graphData);

        while (! empty($graphData) && false) {
            $page++;
            $graphData = $this->makeSparqlService()->performSparqlQuery($this->getServicesQuery($limit, ($limit * $page)));

            // Transform the data in a compatible format
            $data[] = $this->transform($graphData);

            continue;
        }

        return $data;
    }

    /**
     * Create and return a SparqlService object
     *
     * @return App\Services\SparqlService
     */
    private function makeSparqlService()
    {
        return new SparqlService(
            env('SPARQL_ENDPOINT'),
            env('SPARQL_ENDPOINT_USERNAME'),
            env('SPARQL_ENDPOINT_PASSWORD')
        );
    }

    /**
     * Transform the data into an internal model compatible list of services
     *
     * @param  string $services A string containing the graph data
     * @return array
     */
    private function transform($data)
    {
        $graph = $this->parseResults($data);

        $services = [];

        collect($graph->allOfType('foaf:Agent'))
                        ->each(function ($agent) use (&$services) {
                            $services[] = [
                                'label' => $this->getLiteral($agent, 'foaf:name'),
                                'uri' => $agent->getUri(),
                                'identifier' => $this->getLiteral($agent, 'dcterms:identifier'),
                                'source' => strtolower($this->getLiteral($agent, 'dcterms:source'))
                            ];
                        });

        return $services;
    }

    /**
     * Get the literal value from a resource
     *
     * @param  EasyRdf_Resource $resource
     * @return string
     */
    private function getLiteral($resource, string $property)
    {
        $literal = $resource->getLiteral($property);

        if (empty($literal)) {
            return '';
        }

        return $literal->getValue();
    }

    /**
     * Parse the graph results and return an internal model compatible list
     * of the services (e.g. conform our Eloquent model)
     *
     * @param  Graph  $graph
     * @return [type]
     */
    private function parseResults($data)
    {
        $graph = new Graph();

        $parser = new Parser();
        $parser->parse($graph, $data, 'turtle', null);

        return $graph;
    }

    /**
     * Return the SPARQL query that fetches all of the available services
     * TODO: transform everything to a SELECT statement with paging + change transform accordingly
     *
     * @param  int    $limit
     * @param  int    $offset
     * @return string
     */
    private function getServicesQuery($limit, $offset)
    {
        return 'CONSTRUCT { ?agent ?p ?o }
                WHERE {
                    {
                        ?agent a foaf:Agent;
                        <http://purl.org/dc/terms/source> "RECREATEX"^^xsd:string ;
                        <http://purl.org/dc/terms/identifier> ?recreatexID.
                        ?agent ?p ?o.
                    }
                }';
    }
}
