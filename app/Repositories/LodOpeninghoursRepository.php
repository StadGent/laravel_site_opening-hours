<?php

namespace App\Repositories;

use App\Services\SparqlService;
use EasyRdf\Serialiser\Turtle as TurtleSerialiser;

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
     * @param  int           $openinghoursId
     * @param  EasyRdf\Graph $graph
     * @return bool
     */
    public function update($service, $channel, $openinghoursId, $graph)
    {
        extract($this->makeDeleteAndInsertSparqlQuery($service, $channel, $openinghoursId, $graph));

        foreach ($deleteQueries as $deleteQuery) {
            $deleteQuery = $header . ' ' . $deleteQuery;

            $this->makeSparqlService()->performSparqlQuery($deleteQuery, 'POST');
        }

        $newTriples = $header . ' ' . $newTriples;
        return $this->makeSparqlService()->performSparqlQuery($newTriples, 'POST');
    }

    /**
     * Delete channel
     *
     * @param int $channelId
     */
    public function deleteChannel($channelId)
    {
        $queries = $this->createRemoveChannelQueries($channelId);
        $result = true;
        foreach ($queries as $query) {
            $result = $this->makeSparqlService()->performSparqlQuery($query, 'POST') && $result;
        }

        return $result;
    }

    /**
     * Delete openinghours
     *
     * @param  int  $openinghoursId
     * @return bool
     */
    public function deleteOpeninghours($openinghoursId)
    {
        $queries = $this->createRemoveOpeninghoursQueries($openinghoursId);
        $result = true;
        foreach ($queries as $query) {
            $result = $this->makeSparqlService()->performSparqlQuery($query, 'POST') && $result;
        }

        return $result;
    }

    /**
     * Make a SPARQL delete statement and return new triples that need to be inserted
     * for a specific openinghours object
     *
     * @param  string        $serviceUri
     * @param  array         $channel
     * @param  int           $openinghoursId
     * @param  EasyRdf\Graph $graph
     * @return array
     */
    private function makeDeleteAndInsertSparqlQuery($service, $channel, $openinghoursId, $graph)
    {
        $serviceUri = createServiceUri($service['id']);
        $channelUri = createChannelUri($channel['id']);

        // Get the graph to write too
        $graphName = $this->getGraphName();

        $serialiser = new TurtleSerialiser();

        $triples = $serialiser->serialise($graph->getGraph(), 'turtle');

        list($triples, $headers) = $this->splitHeadersAndTriples($triples);

        $deleteQueries = $this->createRemoveOpeninghoursQueries($openinghoursId);

        return [
            'header' => $headers,
            'deleteQueries' => $deleteQueries,
            'newTriples' => "INSERT DATA { GRAPH <$graphName> { $triples } }",
        ];
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
        $sparqlService = app('SparqlService');
        $sparqlService->setClient();

        return $sparqlService;
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

    /**
     * Check if a URI has any triples in the graph
     *
     * Uses a ASK query to avoid DELETE operations
     * on non-existent data,causing 600s Virtuoso timeouts.
     *
     * @param  string $uri
     * @return bool
     */
    private function uriExistsInGraph($uri)
    {
        $graph = $this->getGraphName();
        $askQuery = "ASK FROM <$graph> WHERE { <$uri> ?p ?o . }";

        return $this->makeSparqlService()->ask($askQuery);
    }

    /**
     * Create and return SPARQL queries that delete a channel triple
     * and all of its underlying triples (Openinghours, Vcalendar, Vcomponent, Vevent)
     *
     * Returns an empty array if no data exists for the channel URI,
     * avoiding expensive recursive graph traversal on empty data.
     *
     * Uses multiple targeted DELETE queries instead of one recursive query
     * to avoid rdf:rest* traversal and unbound predicate scans timing out on Virtuoso.
     *
     * @param  int   $channelId
     * @return array
     */
    private function createRemoveChannelQueries($channelId)
    {
        $channelUri = createChannelUri($channelId);
        $graph = $this->getGraphName();

        // FIX: check if data exists before running expensive recursive DELETE
        if (!$this->uriExistsInGraph($channelUri)) {
            return [];
        }

        $oh   = 'http://semweb.datasciencelab.be/ns/oh#';
        $rdf  = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $ical = 'http://www.w3.org/2002/12/cal/ical#';

        return [
            // Step 1: delete the channel node itself
            "WITH <$graph>
            DELETE WHERE {
                <$channelUri> ?p ?o .
            }",

            // Step 2: delete openinghours nodes linked to the channel
            "WITH <$graph>
            DELETE {
                ?oh ?p ?o .
            }
            WHERE {
                <$channelUri> ?x ?oh .
                ?oh a <{$oh}OpeningHours> .
                ?oh ?p ?o .
            }",

            // Step 3: delete calendars linked to openinghours
            "WITH <$graph>
            DELETE {
                ?cal ?p ?o .
            }
            WHERE {
                <$channelUri> ?x ?oh .
                ?oh a <{$oh}OpeningHours> .
                ?oh <{$oh}calendar> ?cal .
                ?cal ?p ?o .
            }",

            // Step 4: delete vcalendars linked to calendar heads
            "WITH <$graph>
            DELETE {
                ?vcal ?p ?o .
            }
            WHERE {
                <$channelUri> ?x ?oh .
                ?oh a <{$oh}OpeningHours> .
                ?oh <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal a <{$ical}Vcalendar> .
                ?vcal ?p ?o .
            }",

            // Step 5: delete vevents linked to vcalendars
            "WITH <$graph>
            DELETE {
                ?vevent ?p ?o .
            }
            WHERE {
                <$channelUri> ?x ?oh .
                ?oh a <{$oh}OpeningHours> .
                ?oh <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal ?icalVcomp ?vevent .
                ?vevent a <{$ical}Vevent> .
                ?vevent ?p ?o .
            }",

            // Step 6: delete rrules linked to vevents
            "WITH <$graph>
            DELETE {
                ?rrule ?p ?o .
            }
            WHERE {
                <$channelUri> ?x ?oh .
                ?oh a <{$oh}OpeningHours> .
                ?oh <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal ?icalVcomp ?vevent .
                ?vevent a <{$ical}Vevent> .
                ?vevent ?pVevent ?rrule .
                ?rrule ?p ?o .
            }",
        ];
    }

    /**
     * Create and return SPARQL queries that delete an openinghours triple
     * and all of its underlying triples (Vcalendar, Vcomponent, Vevent)
     *
     * Returns an empty array if no data exists for the openinghours URI,
     * avoiding expensive recursive graph traversal on empty data.
     *
     * Uses multiple targeted DELETE queries instead of one recursive query
     * to avoid rdf:rest* traversal and unbound predicate scans timing out on Virtuoso.
     *
     * @param  int   $openinghoursId
     * @return array
     */
    private function createRemoveOpeninghoursQueries($openinghoursId)
    {
        $openinghoursUri = createOpeninghoursUri($openinghoursId);
        $graph = $this->getGraphName();

        // FIX: check if data exists before running expensive DELETE
        // The old query was scanning the entire graph for nested structures
        // that don't exist, causing Virtuoso to timeout after 600 seconds.
        if (!$this->uriExistsInGraph($openinghoursUri)) {
            return [];
        }

        $oh   = 'http://semweb.datasciencelab.be/ns/oh#';
        $rdf  = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
        $ical = 'http://www.w3.org/2002/12/cal/ical#';

        return [
            // Step 1: delete the openinghours node itself
            "WITH <$graph>
            DELETE WHERE {
                <$openinghoursUri> ?p ?o .
            }",

            // Step 2: delete calendars linked to openinghours
            "WITH <$graph>
            DELETE {
                ?cal ?p ?o .
            }
            WHERE {
                <$openinghoursUri> <{$oh}calendar> ?cal .
                ?cal ?p ?o .
            }",

            // Step 3: delete vcalendars linked to calendar heads
            "WITH <$graph>
            DELETE {
                ?vcal ?p ?o .
            }
            WHERE {
                <$openinghoursUri> <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal a <{$ical}Vcalendar> .
                ?vcal ?p ?o .
            }",

            // Step 4: delete vevents linked to vcalendars
            "WITH <$graph>
            DELETE {
                ?vevent ?p ?o .
            }
            WHERE {
                <$openinghoursUri> <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal ?icalVcomp ?vevent .
                ?vevent a <{$ical}Vevent> .
                ?vevent ?p ?o .
            }",

            // Step 5: delete rrules linked to vevents
            "WITH <$graph>
            DELETE {
                ?rrule ?p ?o .
            }
            WHERE {
                <$openinghoursUri> <{$oh}calendar> ?cal .
                ?cal <{$rdf}first> ?head .
                ?head ?rdfcal ?vcal .
                ?vcal ?icalVcomp ?vevent .
                ?vevent a <{$ical}Vevent> .
                ?vevent ?pVevent ?rrule .
                ?rrule ?p ?o .
            }",
        ];
    }
}
