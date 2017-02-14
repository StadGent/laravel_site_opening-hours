<?php

namespace App\Repositories;

use App\Models\Openinghours;
use EasyRdf_Graph as Graph;
use EasyRdf_Serialiser_Turtle as TurtleSerialiser;
use EasyRdf_Literal as Literal;
use EasyRdf_Literal_Boolean as BooleanLiteral;
use EasyRdf_Literal_Integer as IntegerLiteral;
use EasyRdf_Literal_DateTime as DateTimeLiteral;
use DB;

class OpeninghoursRepository extends EloquentRepository
{
    public function __construct(Openinghours $openinghours)
    {
        parent::__construct($openinghours);
    }

    public function getById($id)
    {
        $openinghours = $this->model->find($id);

        if (empty($openinghours)) {
            return [];
        }

        $result = $openinghours->toArray();
        $result['calendars'] = [];

        $calendars = app('CalendarRepository');

        $openinghours->with('calendars');

        foreach ($openinghours->calendars as $calendar) {
            $calendar = $calendars->getById($calendar->id);

            $result['calendars'][] = $calendar;
        }

        return $result;
    }

    /**
     * Return a boolean indicating if an openinghours object is "active",
     * meaning that its timespan is relevant "now".
     *
     * @param  integer $openinghoursId The id of the openinghours object
     * @return boolean
     */
    public function isActive($openinghoursId)
    {
        $openinghours = $this->getById($openinghoursId);

        if (empty($openinghours)) {
            return false;
        }

        return carbonize()->between(carbonize($openinghours['start_date']), carbonize($openinghours['end_date']));
    }

    /**
     * Create a graph from the openinghours object
     * containing calendar and event data
     *
     * @param  integer        $openinghoursId
     * @return \EasyRdf_Graph
     */
    public function getOpeninghoursGraph($openinghoursId)
    {
        $openinghours = $this->model->find($openinghoursId);
        $calendars = $openinghours->calendars();
        $calendars = $calendars->with('events')->get()->toArray();
        $channel = $openinghours->channel->toArray();

        $openinghoursGraph = new Graph();

        \EasyRdf_Namespace::set('oh', 'http://semweb.datasciencelab.be/ns/oh#');
        \EasyRdf_Namespace::set('ical', 'http://www.w3.org/2002/12/cal/ical#');

        if (! empty($openinghours)) {
            $openinghoursResource = $openinghoursGraph->resource(
                env('BASE_URI') . '/openinghours/' . $openinghoursId,
                'oh:OpeningHours'
            );

            $openinghoursResource->addResource('oh:type', env('BASE_URI') . '/channel/' . $channel['label']);

            // Add the calendars taken into account the priority of the calendar
            // Sort the calendars first
            $calendars = array_sort($calendars, function ($calendar) {
                return $calendar['priority'];
            });

            $calendarList = $openinghoursGraph->newBNode('rdf:List');

            // Add the List to the openinghours object
            $openinghoursResource->addResource('oh:calendar', $calendarList);

            foreach ($calendars as $calendar) {
                $calendarResource = $openinghoursGraph->newBNode('oh:Calendar');
                $calendarList->addResource('rdf:first', $calendarResource);

                // Make a calendar Resource
                $rdfCalendarResource = $openinghoursGraph->newBNode('ical:Vcalendar');

                if ($calendar['closinghours']) {
                    $rdfCalendarResource->addLiteral('oh:closinghours', $this->createLiteral('boolean', true));
                } else {
                    $rdfCalendarResource->addLiteral('oh:closinghours', $this->createLiteral('boolean', false));
                }

                foreach ($calendar['events'] as $event) {
                    $eventResource = $openinghoursGraph->newBNode('ical:Vevent');
                    $eventResource->addLiteral('ical:dtend', $event['end_date']);
                    $eventResource->addLiteral('ical:dtstart', $event['end_date']);

                    $rruleResource = $openinghoursGraph->newBNode();
                    $eventResource->addResource('ical:rrule', $rruleResource);

                    // Add the rrule properties
                    $pieces = explode(';', $event['rrule']);

                    foreach ($pieces as $rrulePiece) {
                        $parts = explode('=', $rrulePiece);

                        $rruleResource->addLiteral('ical:' . strtolower($parts[0]), $parts[1]);
                    }

                    // Add the event resource to the calendar
                    $rdfCalendarResource->addResource('ical:Vcomponent', $eventResource);
                }

                $calendarResource->addResource('oh:rdfcal', $rdfCalendarResource);

                // Add a new list as the 'rest' of the existing list
                $calendarListRest = $openinghoursGraph->newBNode('rdf:List');
                $calendarList->addResource('rdf:rest', $calendarListRest);

                // Move the current list to the new list (= rdf:rest)
                $calendarList = $calendarListRest;
            }
        }

        $serialiser = new TurtleSerialiser();
        dd($serialiser->serialise($openinghoursGraph, 'turtle'));

        return $openinghoursGraph;
    }

    /**
     * Create a new literal
     *
     * @param  string     $type
     * @param  mixed      $value
     * @return Literal
     * @throws \Exception
     */
    private function createLiteral($type, $value)
    {
        switch ($type) {
            case 'integer':
                return new IntegerLiteral($value, '', 'xsd:integer');
                break;
            case 'boolean':
                return new BooleanLiteral($value, '', 'xsd:boolean');
                break;
            case 'datetime':
                return new DateTimeLiteral($value, '', 'xsd:datetime');
                break;
        }

        throw new \Exception('Literal type ' . $type . ' is not supported');
    }

    /**
     * Get all of the active openinghours for a channel and service
     *
     * @param  string $serviceUri The URI of the service
     * @param  string $channel    The name of the channel
     * @return array
     */
    public function getAllForServiceAndChannel($serviceUri, $channel)
    {
        $results = DB::select(
            'SELECT openinghours.id
            FROM openinghours
            JOIN channels ON channels.id = openinghours.channel_id
            JOIN services ON services.id = channels.service_id
            WHERE services.uri = ? AND channels.label = ? AND openinghours.active = 1',
            [$serviceUri, $channel]
        );

        $openinghoursIds = [];

        foreach ($results as $result) {
            $openinghoursIds[] = $result->id;
        }

        return $this->model->whereIn('id', $openinghoursIds)->get();
    }
}
