<?php

namespace App\Repositories;

use App\Services\SparqlService;
use EasyRdf_Graph as Graph;

/**
 * This class is responsible for fetching services from a SPARQL endpoint
 */
class LodServicesRepository
{
    /**
     * Return the services fetched from the SPARQL endpoint
     * in a structure that is compatible with our internal Service model
     *
     * @param  string $type recreatex or vesta
     * @return array
     */
    public function fetchServices($type)
    {
        $limit = 100;
        $page = 0;

        $data = [];

        $fetchFunction = 'get' . ucfirst($type) . 'ServicesQuery';

        $semanticResults = $this->makeSparqlService()->performSparqlQuery(static::$fetchFunction(), 'GET', 'json');

        // Transform the data in a compatible format
        $transformedData = $this->transform($semanticResults);

        $data = array_merge($data, array_values($transformedData));

        while (! empty($transformedData)) {
            $page++;
            $semanticResults = $this->makeSparqlService()->performSparqlQuery(static::$fetchFunction($limit, ($limit * $page)), 'GET', 'json');

            // Transform the data in a compatible format
            $transformedData = $this->transform($semanticResults);

            if (! empty($transformedData)) {
                $data = array_merge($data, array_values($transformedData));
            }
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
        $services = [];

        $data = json_decode($data, true);
        $data = array_get($data, 'results.bindings', []);

        if (empty($data)) {
            return $services;
        }

        collect($data)->each(function ($agent) use (&$services) {
            $identifier = array_get($agent, 'identifier.value', '');

            if (! empty($identifier)) {
                $services[] = [
                    'label' => array_get($agent, 'name.value', ''),
                    'uri' => array_get($agent, 'agent.value', ''),
                    'identifier' => $identifier
                ];
            }
        });

        return $services;
    }

    /**
     * Return the SPARQL query that fetches all of the available services
     * coming from VESTA
     *
     * @param  int    $limit
     * @param  int    $offset
     * @return string
     */
    public static function getVestaServicesQuery($limit = 100, $offset = 0)
    {
        return 'SELECT DISTINCT ?agent ?identifier ?name
                WHERE {
                {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> "VESTA"^^xsd:string ;
                    <http://purl.org/dc/terms/type> "Stad Gent"^^<http://www.w3.org/2001/XMLSchema#string>;
                    <http://purl.org/dc/terms/type> "Dienst"^^<http://www.w3.org/2001/XMLSchema#string>;
                    <http://purl.org/dc/terms/identifier> ?identifier;
                    foaf:name ?name.
                }
                UNION
                {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> "VESTA"^^xsd:string;
                    <http://purl.org/dc/terms/type> "Stad Gent"^^<http://www.w3.org/2001/XMLSchema#string>;
                    <http://purl.org/dc/terms/type> "Departement"^^<http://www.w3.org/2001/XMLSchema#string>;
                    <http://purl.org/dc/terms/identifier> ?identifier;
                    foaf:name ?name.
                }
                UNION
                {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> "VESTA"^^xsd:string;
                    <http://purl.org/dc/terms/type> "OCMW"^^<http://www.w3.org/2001/XMLSchema#string>;
                    <http://purl.org/dc/terms/identifier> ?identifier;
                    foaf:name ?name.
                }
                }  ORDER BY ?name ' . " LIMIT $limit OFFSET $offset";
    }

    /**
     * Return the SPARQL query that fetches services of the available services
     * coming from RECREATEX
     *
     * @param  int    $limit
     * @param  int    $offset
     * @return string
     */
    public static function getRecreatexServicesQuery($limit = 100, $offset = 0)
    {
        return 'SELECT ?agent ?identifier ?name
                WHERE
                {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> "RECREATEX"^^xsd:string ;
                    <http://purl.org/dc/terms/identifier> ?identifier.
                    ?agent foaf:name ?name.
                } ' . " LIMIT $limit OFFSET $offset";
    }
}
