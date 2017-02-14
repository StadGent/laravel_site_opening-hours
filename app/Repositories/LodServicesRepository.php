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
     * Return the services fetch from the SPARQL endpoint
     *
     * @return array
     */
    public function fetchServices()
    {
        // $data = $this->makeSparqlService()->performSparqlQuery($this->getServicesQuery());

        // Get mock data for now
        $data = $this->getMockServicesData();

        // Return the services in a compatible model
        return $this->transform($data);
    }

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
    private function transform(string $data)
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

    private function getMockServicesData()
    {
        return '@prefix ns0:    <http://purl.org/oslo/ns/localgov#> .
                @prefix ns1:    <https://qa.stad.gent/id/agents/> .
                @prefix ns2:    <https://qa.stad.gent/id/agents/fdcd1f73-1f89-e111-8092-0050569805c9/> .
                ns1:fdcd1f73-1f89-e111-8092-0050569805c9    ns0:mailingLocation ns2:location .
                @prefix rdf:    <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
                @prefix foaf:   <http://xmlns.com/foaf/0.1/> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    rdf:type    foaf:Agent .
                @prefix xsd:    <http://www.w3.org/2001/XMLSchema#> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    foaf:name   "Bouw- soms Woontoezicht"^^xsd:string .
                @prefix ns6:    <http://purl.org/dc/terms/> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    ns6:identifier  "fecd1f73-1f89-e111-8092-0050569805c9"^^xsd:string .
                @prefix ns7:    <http://schema.org/> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    ns7:keywords    "Stedenbouw"^^xsd:string ;
                    ns6:source  "VESTA"^^xsd:string ;
                    ns6:type    "Stad Gent"^^xsd:string ,
                        ""^^xsd:string ,
                        "Dienst"^^xsd:string .
                @prefix rdfs:   <http://www.w3.org/2000/01/rdf-schema#> .
                @prefix ns9:    <https://qa.stad.gent/data/agents/> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    rdfs:definedBy  ns9:fecd1f73-1f89-e111-8092-0050569805c9 .
                @prefix ns10:   <https://qa.stad.gent/id/agents/fecd1f73-1f89-e111-8092-0050569805c9/> .
                ns1:fecd1f73-1f89-e111-8092-0050569805c9    ns0:contact ns10:contact ;
                    ns0:mailingLocation ns10:location .
                ns1:ffcd1f73-1f89-e111-8092-0050569805c9    rdf:type    foaf:Agent ;
                    foaf:name   "Bouw- soms Woontoezicht"^^xsd:string ;
                    ns6:identifier  "ffcd1f73-1f89-e111-8092-0050569805c9"^^xsd:string ;
                    ns7:keywords    "Stedenbouw"^^xsd:string ;
                    ns6:source  "VESTA"^^xsd:string ;
                    ns6:type    "Stad Gent"^^xsd:string ,
                        ""^^xsd:string ,
                        "Dienst"^^xsd:string ;
                    rdfs:definedBy  ns9:ffcd1f73-1f89-e111-8092-0050569805c9 .
                @prefix ns11:   <https://qa.stad.gent/id/agents/ffcd1f73-1f89-e111-8092-0050569805c9/> .
                ns1:ffcd1f73-1f89-e111-8092-0050569805c9    ns0:contact ns11:contact ;
                    ns0:mailingLocation ns11:location .';
    }
}
