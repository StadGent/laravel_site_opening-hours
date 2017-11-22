<?php

namespace App\Repositories;

use App\Models\Openinghours;
use EasyRdf_Graph as Graph;
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

        $calendars = app('CalendarRepository');

        $result = $openinghours->toArray();
        $result['calendars'] = [];

        $openinghours->with('calendars');

        foreach ($openinghours->calendars as $calendar) {
            $calendar = $calendars->getById($calendar->id);

            $result['calendars'][] = $calendar;
        }

        return $result;
    }

    /**
     * Get the full object: openinghours with related channel and service
     *
     * @param  int   $id
     * @return array
     */
    public function getFullObjectById($id)
    {
        $openinghours = $this->model->with('channel.service')->find($id);

        if (empty($openinghours)) {
            return [];
        }

        return $openinghours->toArray();
    }

    /**
     * Store new openinghours
     *
     * @param  array $properties
     * @return int   The ID of the new openinghours object
     */
    public function store(array $properties)
    {
        $properties['active'] = $this->isOpeninghoursRelevantNow($properties);

        return parent::store($properties);
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

        return $this->isOpeninghoursRelevantNow($openinghours);
    }

    /**
     * Check if the openinghours timestamp covers "today",
     * meaning the timespan is relevant now.
     *
     * @param  array $openinghours
     * @return bool
     */
    public function isOpeninghoursRelevantNow($openinghours)
    {
        if (empty($openinghours['start_date'])) {
            // If no start date is passed we can assume it starts from today or earlier
            $openinghours['start_date'] = carbonize()->subMonth()->toDateString();
        }

        return carbonize()->between(carbonize($openinghours['start_date']), carbonize($openinghours['end_date']));
    }

    /**
     * Create a semantic data structure containing all openinghours for the channel
     *
     * @param  integer        $channelId
     * @return \EasyRdf_Graph
     */
    public function getOpeninghoursGraphForChannel($channelId)
    {
        $openinghoursGraph = new Graph();
        $channelModel = \App\Models\Channel::query()->find($channelId);
        $channel = $openinghoursGraph->resource(
            createChannelUri($channelId),
            'cv:Channel'
        );

        $channel->set('http://www.w3.org/2004/02/skos/core#prefLabel', $channelModel->label);

        return $openinghoursGraph;
    }

    /**
     * Get all of the openinghours for a channel and service
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

    /**
     * Get active openinghours for a service and channel for a given timerange
     *
     * @param  string $serviceUri The URI of the service
     * @param  string $channel    The name of the channel
     * @param  Carbon $start
     * @param  Carbon $end
     * @return array
     */
    public function getForServiceAndChannel($serviceUri, $channel, $start, $end)
    {
        // Get the openinghours in which the start/end either lays partially in the given
        // start-end range or where the given start-end lays in openinghours start-end range
        $results = DB::select(
            'SELECT openinghours.id
            FROM openinghours
            JOIN channels ON channels.id = openinghours.channel_id
            JOIN services ON services.id = channels.service_id
            WHERE services.uri = ? AND channels.label = ? AND
            (
                (openinghours.start_date >= ? AND openinghours.start_date <= ?)
                OR
                (openinghours.end_date >= ? AND openinghours.end_date <= ?)
                OR
                (openinghours.start_date <= ? AND openinghours.end_date >= ?)
            )',
            [$serviceUri, $channel,
            $start->startOfDay()->toIso8601String(), $end->startOfDay()->toIso8601String(),
            $start->startOfDay()->toIso8601String(), $end->startOfDay()->toIso8601String(),
            $start->startOfDay()->toIso8601String(), $end->startOfDay()->toIso8601String()
            ]
        );

        $openinghoursIds = [];

        foreach ($results as $result) {
            $openinghoursIds[] = $result->id;
        }

        return $this->model->whereIn('id', $openinghoursIds)->get();
    }
}
