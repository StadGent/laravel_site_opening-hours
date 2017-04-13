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
     * @param  int           $openinghoursId
     * @param  EasyRdf_Graph $graph
     * @return bool
     */
    public function update($service, $channel, $openinghoursId, $graph)
    {
        list($deleteQuery, $newTriples) = $this->makeDeleteAndInsertSparqlQuery($service, $channel, $openinghoursId, $graph);

        $this->makeSparqlService()->performSparqlQuery($deleteQuery, 'GET');
        $this->makeSparqlService()->performSparqlQuery($newTriples, 'POST');
    }

    /**
     * Delete channel
     *
     * @param int $channelId
     */
    public function deleteChannel($channelId)
    {
        $channelUri = createChannelUri($channelId);
        $graph = env('SPARQL_WRITE_GRAPH');

        if (empty($graph)) {
            \Log::warning('No graph was configured, we could not delete the openinghours');

            return false;
        }

        $query = $this->createRemoveChannelQuery($channelId);

        $result = $this->makeSparqlService()->performSparqlQuery($query, 'GET');
    }

    /**
     * Delete openinghours
     *
     * @param  int  $openinghoursId
     * @return bool
     */
    public function deleteOpeninghours($openinghoursId)
    {
        $openinghoursUri = createOpeninghoursUri($openinghoursId);
        $graph = env('SPARQL_WRITE_GRAPH');

        if (empty($graph)) {
            \Log::warning('No graph was configured, we could not delete the openinghours');

            return false;
        }

        $query = $this->createRemoveOpeninghoursQuery($openinghoursId);

        $result = $this->makeSparqlService()->performSparqlQuery($query, 'GET');
    }

    /**
     * Make a SPARQL delete statement and return new triples that need to be inserted
     *
     * @param  string        $serviceUri
     * @param  array         $channel
     * @param  int           $openinghoursId
     * @param  EasyRdf_Graph $graph
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

        $deleteQuery = $this->createRemoveOpeninghoursQuery($openinghoursId);

        return [$headers . ' ' . $deleteQuery, $headers . ' ' . $triples];
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

    /**
     * Create and return a SPARQL query that deletes a channel triple
     * and all of its underlying triples (Openinghours, Vcalendar, Vcomponent, Vevent)
     *
     * @param  int    $channelId
     * @return string
     */
    private function createRemoveChannelQuery($channelId)
    {
        $channelUri = createChannelUri($channelId);
        $graph = env('SPARQL_WRITE_GRAPH');

        return "WITH <$graph>
            delete {
                ?channel a <http://data.europa.eu/m8g/Channel>.
                ?channel <http://data.europa.eu/m8g/isOwnedBy> ?service.
                ?channel ?openinghours ?oh.
                ?oh ?x ?z.
                ?oh <http://semweb.datasciencelab.be/ns/oh#calendar> ?list.
                ?calendar a <http://semweb.datasciencelab.be/ns/oh#Calendar>.
                ?calendar ?rdfcal ?vcal.
                ?vcal ?icalVcomp ?vevent.
                ?vcal <http://semweb.datasciencelab.be/ns/oh#closinghours> ?closinghours.
                ?vcal a <http://www.w3.org/2002/12/cal/ical#Vcalendar>.
                ?vevent ?pVevent ?rrule.
                ?rrule ?pRrule ?rruleObj.
                ?rest ?p ?o.
            }
            WHERE {
                ?channel a <http://data.europa.eu/m8g/Channel>.
                ?channel <http://data.europa.eu/m8g/isOwnedBy> ?service.
                FILTER(?channel = <$channelUri>)
                OPTIONAL {
                    ?channel ?openinghours ?oh.
                    ?oh a <http://semweb.datasciencelab.be/ns/oh#OpeningHours>;
                    <http://semweb.datasciencelab.be/ns/oh#calendar> ?list.
                    ?oh ?x ?z.
                    ?list rdf:first* ?calendar.
                    ?list rdf:rest* ?rest.
                    OPTIONAL {?rest ?p ?o.}
                    ?calendar a <http://semweb.datasciencelab.be/ns/oh#Calendar>.
                    ?calendar ?rdfcal ?vcal.
                    ?vcal ?icalVcomp ?vevent.
                    ?vcal <http://semweb.datasciencelab.be/ns/oh#closinghours> ?closinghours.
                    ?vcal a <http://www.w3.org/2002/12/cal/ical#Vcalendar>.
                    ?vevent a <http://www.w3.org/2002/12/cal/ical#Vevent>.
                    ?vevent ?pVevent ?rrule.
                    ?vevent a <http://www.w3.org/2002/12/cal/ical#Vevent>.
                    OPTIONAL {
                        ?vevent ?pVevent ?rrule.
                        ?vevent a <http://www.w3.org/2002/12/cal/ical#Vevent>.
                        OPTIONAL {?rrule ?pRrule ?rruleObj.}
                    }
                }
        }";
    }

    /**
     * Create and return a SPARQL query that deletes an openinghours triple
     * and all of its underlying triples (Vcalendar, Vcomponent, Vevent)
     *
     * @param  int    $openinghoursId
     * @return string
     */
    private function createRemoveOpeninghoursQuery($openinghoursId)
    {
        $openinghoursUri = createOpeninghoursUri($openinghoursId);
        $graph = env('SPARQL_WRITE_GRAPH');

        return "WITH <$graph>
            delete {
                ?oh ?x ?z.
                ?channel ?openinghours ?oh.
                ?oh <http://semweb.datasciencelab.be/ns/oh#calendar> ?list.
                ?calendar a <http://semweb.datasciencelab.be/ns/oh#Calendar>.
                ?calendar ?rdfcal ?vcal.
                ?vcal ?icalVcomp ?vevent.
                ?vcal <http://semweb.datasciencelab.be/ns/oh#closinghours> ?closinghours.
                ?vcal a <http://www.w3.org/2002/12/cal/ical#Vcalendar>.
                ?vevent ?pVevent ?rrule.
                ?rrule ?pRrule ?rruleObj.
                ?rest ?p ?o.
            }
            WHERE {
                ?oh a <http://semweb.datasciencelab.be/ns/oh#OpeningHours>;
                <http://semweb.datasciencelab.be/ns/oh#calendar> ?list.
                ?oh ?x ?z.
                ?channel ?openinghours ?oh.
                FILTER(?oh = <$openinghoursUri>)
                ?list rdf:first* ?calendar.
                ?list rdf:rest* ?rest.
                OPTIONAL {?rest ?p ?o.}
                ?calendar a <http://semweb.datasciencelab.be/ns/oh#Calendar>.
                ?calendar ?rdfcal ?vcal.
                ?vcal ?icalVcomp ?vevent.
                ?vcal <http://semweb.datasciencelab.be/ns/oh#closinghours> ?closinghours.
                ?vcal a <http://www.w3.org/2002/12/cal/ical#Vcalendar>.
                ?vevent a <http://www.w3.org/2002/12/cal/ical#Vevent>.
                    OPTIONAL {
                        ?vevent ?pVevent ?rrule.
                        ?vevent a <http://www.w3.org/2002/12/cal/ical#Vevent>.
                        OPTIONAL {?rrule ?pRrule ?rruleObj.}
                    }
        }";
    }
}
