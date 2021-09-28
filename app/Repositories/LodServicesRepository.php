<?php

namespace App\Repositories;

use Illuminate\Support\Arr;

/**
 * This class is responsible for fetching services from a SPARQL endpoint
 */
class LodServicesRepository
{
    /**
     * Return the services fetched from the SPARQL endpoint
     * in a structure that is compatible with our internal Service model
     *
     * @param  string $type recreatex, vesta or publicToilets
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

        while (!empty($transformedData)) {
            $page++;
            $semanticResults = $this->makeSparqlService()->performSparqlQuery(static::$fetchFunction($limit, ($limit * $page)), 'GET', 'json');

            // Transform the data in a compatible format
            $transformedData = $this->transform($semanticResults);

            if (!empty($transformedData)) {
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
        $sparqlService = app('SparqlService');
        $sparqlService->setClient();

        return $sparqlService;
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
        $data = Arr::get($data, 'results.bindings', []);

        if (empty($data)) {
            return $services;
        }

        collect($data)->each(function ($agent) use (&$services) {
            $identifier = Arr::get($agent, 'identifier.value', '');

            if (!empty($identifier)) {
                $services[] = [
                    'label' => Arr::get($agent, 'name.value', ''),
                    'uri' => Arr::get($agent, 'agent.value', ''),
                    'identifier' => $identifier,
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
        $query = 'SELECT DISTINCT ?agent ?identifier ?name
                FROM <http://stad.gent/agents/>
                WHERE {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> ?source ;
                    <http://purl.org/dc/terms/identifier> ?identifier;
                        foaf:name ?official_name.
                        OPTIONAL { ?agent  foaf:nickname ?nickname}
                    filter strstarts(?source, "VESTA"^^xsd:string)
                        BIND(IF(BOUND(?nickname) && ?nickname != "" && REPLACE(UCASE(?nickname)," ","") != REPLACE(UCASE(?official_name)," ",""), CONCAT(?official_name, " (", ?nickname, ")"), ?official_name) as ?name)
                } ORDER BY ?name';

        if ($limit) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        return $query;
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
        $query = 'SELECT ?agent ?identifier ?name
                FROM <http://stad.gent/agents/>
                WHERE
                {
                    ?agent a foaf:Agent;
                    <http://purl.org/dc/terms/source> "RECREATEX"^^xsd:string ;
                    <http://purl.org/dc/terms/identifier> ?identifier.
                    ?agent foaf:name ?name.
                } ';

        if ($limit) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        return $query;
    }

    public static function getPublicToiletsServicesQuery($limit = 100, $offset = 0)
    {
        $query = 'PREFIX schema: <http://schema.org/>
                SELECT DISTINCT ?agent ?identifier ?name ?source
                FROM <http://stad.gent/agents/>
                WHERE {
                    ?agent a foaf:Agent, schema:PublicToilet;
                    <http://purl.org/dc/terms/source> ?source ;
                    <http://purl.org/dc/terms/identifier> ?identifier;
                    foaf:name ?official_name.
                    OPTIONAL { ?agent  foaf:nickname ?nickname}
                    BIND(IF(BOUND(?nickname) && ?nickname != "" && REPLACE(UCASE(?nickname)," ","") != REPLACE(UCASE(?official_name)," ",""), CONCAT(?official_name, " (", ?nickname, ")"), ?official_name) as ?name)
                } ORDER BY ?name';

        if ($limit) {
            $query .= " LIMIT $limit OFFSET $offset";
        }

        return $query;
    }
}
