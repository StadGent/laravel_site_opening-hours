<?php

namespace App\Formatters;

use EasyRdf_Serialiser_JsonLd as JsonLdSerialiser;

/**
 * Formatter class for Openinghours
 * renders given data into given format
 */
class Openinghours extends Formatter
{

    public $serviceUri;

    const OUTPUT_MAPPER = [
        'json'    => 'toJSON',
        'json-ld' => 'toJSONLD',
        'html'    => 'toHTML',
        'text'    => 'toTEXT',
        'csv'     => 'toCSV',
        'xml'     => 'toXML',
    ];

    /**
     * Render data according to the given format
     *
     * @param  string $format to match with available formats
     * @param  array $data   data to transform
     * @return mixed         formatted data
     */
    public function render($format, $data)
    {
        if (!isset(self::OUTPUT_MAPPER[$format])) {
            throw new \Exception("The given format " . $format . " is not available in formatter " . self::class, 1);
        }

        if (!$data) {
            throw new \Exception("No data given for formatter" . self::class, 1);
        }

        $this->data    = $data;
        $renderMethode = self::OUTPUT_MAPPER[$format];

        return $this->$renderMethode();
    }

    /**
     * Return a JSON formatted openinghours schedule
     * @return $this->data => laravel puts arrays into json format for us
     */
    protected function toJSON()
    {
        return $this->data;
    }

    /**
     * Return a JSON-LD formatted openinghours schedule
     * TODO: rework how a schedule is returned, some formats
     * need more basic info of the openinghours instead of
     * formatted hours per day, such as this one.
     *
     * @return json-ld
     */
    protected function toJSONLD()
    {
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph   = new \EasyRdf_Graph();
        $service = $graph->resource($this->serviceUri, 'schema:Organization');

        // get a raw render for the week:
        // $channel id + days index in english
        // for each channel create an openinghours specification
        // where the channel URI is also set as some sort of context
        foreach ($this->data as $channelName => $schedule) {

            $channel = \App\Models\Channel::where('label', '=', $channelName)->first();

            $channelSpecification = $graph->resource(createChannelUri($channel->id), 'cv:Channel');
            $channelSpecification->addLiteral('schema:label', $channelName);
            $channelSpecification->addLiteral('schema:openingHours', $this->makeTextForDayInfo($schedule));
            $channelSpecification->addResource('cv:isOwnedBy', $service);
        }
        $serialiser = new JsonLdSerialiser();

        return $serialiser->serialise($graph, 'jsonld');
    }

    /**
     * Render a schedule into HTML based on an array structure
     * @todo use (blade) template ???
     *
     * @return html
     */
    protected function toHTML()
    {
        $formattedSchedule = '<div>';

        foreach ($this->data as $channel => $schedule) {
            $formattedSchedule .= "<h4>$channel</h4>";
            if (!empty($schedule)) {
                if (is_array($schedule)) {
                    foreach ($schedule as $entry) {
                        $formattedSchedule .= "<div>$entry</div>";
                    }
                } else {
                    $formattedSchedule .= "<div>$schedule</div>";
                }
            }
        }

        $formattedSchedule .= '</div>';

        return $formattedSchedule;
    }

    /**
     * Create a readable text
     *
     * @return string
     */
    protected function toTEXT()
    {
        $text = '';

        foreach ($this->data as $channel => $info) {
            $text .= $channel . ': ' . PHP_EOL;
            $text .= $this->makeTextForDayInfo($info);
            $text .= PHP_EOL . PHP_EOL;
        }
        $text = rtrim($text, PHP_EOL);

        return $text;
    }

    /**
     * Print a textual representation of a day schedule
     *
     * @param  string|array $dayInfo
     * @return string
     */
    protected function makeTextForDayInfo($dayInfo)
    {
        $text = '';
        if (is_array($dayInfo)) {
            foreach ($dayInfo as $date => $oh) {
                $text .= date('d-m-Y', strtotime($date)) . ' ' . $oh . PHP_EOL;
            }
        } else {
            $text .= $dayInfo . PHP_EOL;
        }

        return $text;
    }

}
