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
        $data = $this->makeSparqlService()->performSparqlQuery($this->getServicesQuery());

        // Return the services in a compatible model
        return $this->transform($data);
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
     *
     * @return string
     */
    private function getServicesQuery()
    {
        return 'CONSTRUCT {?agent ?p ?o}
                WHERE {
                    {
                        ?agent a foaf:Agent;
                        <http://purl.org/dc/terms/source> "VESTA"^^xsd:string ;
                        <http://purl.org/dc/terms/type> "Stad Gent"^^<http://www.w3.org/2001/XMLSchema#string>;
                        <http://purl.org/dc/terms/type> "Dienst"^^<http://www.w3.org/2001/XMLSchema#string>;
                        <http://purl.org/dc/terms/identifier> ?vestaId;
                        foaf:name ?name.
                        ?agent ?p ?o.
                    }
                    UNION
                    {
                        ?agent a foaf:Agent;
                        <http://purl.org/dc/terms/source> "VESTA"^^xsd:string;
                        <http://purl.org/dc/terms/type> "Stad Gent"^^<http://www.w3.org/2001/XMLSchema#string>;
                        <http://purl.org/dc/terms/type> "Departement"^^<http://www.w3.org/2001/XMLSchema#string>;
                        <http://purl.org/dc/terms/identifier> ?vestaId;
                        foaf:name ?name.
                        ?agent ?p ?o.
                    }
                    UNION
                    {
                        ?agent a foaf:Agent;
                        <http://purl.org/dc/terms/source> "VESTA"^^xsd:string;
                        <http://purl.org/dc/terms/type> "OCMW"^^<http://www.w3.org/2001/XMLSchema#string>;
                        <http://purl.org/dc/terms/identifier> ?vestaId;
                        foaf:name ?name.
                        ?agent ?p ?o.
                    }
                    UNION {
                        ?agent a foaf:Agent;
                        <http://purl.org/dc/terms/source> "RECREATEX"^^xsd:string ;
                        <http://purl.org/dc/terms/identifier> ?recreatexID.
                        ?agent ?p ?o.
                    }
                }';
    }
}
