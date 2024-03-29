<?php

namespace App\Http\Transformers;

use App\Models\Channel;
use App\Models\DayInfo;
use App\Models\Service;
use App\Services\LocaleService;
use Carbon\Carbon;
use EasyRdf\Graph;
use EasyRdf\RdfNamespace;
use EasyRdf\Serialiser\JsonLd as JsonLdSerialiser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OpeninghoursTransformer implements TransformerInterface
{
    const SUPPORTED_FORMATS = [
        'collection' => [
            'application/json' => 'transformJsonCollection',
            'application/ld+json' => 'transformJsonLdCollection',
            'text/plain' => 'transformPlainTextCollection',
            'text/html' => 'transformHtmlTextCollection',
        ],
    ];

    private $start;

    private $end;

    private $from;

    private $until;

    private $service;

    private $localeService;

    private $includeIsOpenNow;

    private $hasOneChannel;

    private $calendarLength;

    /**
     * @return array
     */
    public static function getSupportedFormats()
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * @param Carbon $start
     */
    public function setStart(Carbon $start)
    {
        $this->start = $start;
    }

    /**
     * @param Carbon $end
     */
    public function setEnd(Carbon $end)
    {
        $this->end = $end;
    }

    /**
     * @param Carbon $from
     */
    public function setFrom(Carbon $from)
    {
        $this->from = $from;
    }

    /**
     * @param Carbon $until
     */
    public function setUntil(Carbon $until)
    {
        $this->until = $until;
    }

    /**
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @param LocaleService $localeService
     */
    public function setLocaleService(LocaleService $localeService)
    {
        $this->localeService = $localeService;
    }

    /**
     * @param $includeIsOpenNow
     */
    public function setIncludeIsOpenNow($includeIsOpenNow)
    {
        $this->includeIsOpenNow = $includeIsOpenNow;
    }

    /**
     * @param $hasOneChannel
     */
    public function setHasOneChannel($hasOneChannel)
    {
        $this->hasOneChannel = $hasOneChannel;
    }

    /**
     * @param $calendarLength
     */
    public function setCalendarLength($calendarLength)
    {
        $this->calendarLength = $calendarLength;
    }

    /**
     * @param Collection $channels
     * @return string
     */
    public function transformJsonCollection(Collection $channels)
    {
        $data = $this->getCollectionData($channels);

        if (!$this->includeIsOpenNow) {
            foreach ($data as $channelKey => $channelArr) {
                foreach ($data[$channelKey]['openinghours'] as $openinghourkey => $openinghourArr) {
                    $datestring = $data[$channelKey]['openinghours'][$openinghourkey]->date->toDateString();
                    $data[$channelKey]['openinghours'][$openinghourkey]->date = $datestring;
                }
            }
        }

        if ($this->hasOneChannel) {
            $data = Arr::first($data);
        }
        return json_encode($data);
    }

    /**
     * @param Collection $channels
     * @return array
     */
    private function getCollectionData(Collection $channels)
    {
        $dataCollection = [];

        foreach ($channels as $channel) {
            if (!isset($dataCollection[$channel->id])) {
                $dataCollection[$channel->id] = [
                    'channel' => $channel->label,
                    'channelId' => $channel->id,
                    'channelTypeLabel' => $channel->type ? $channel->type->name : null,
                    'channelTypeId' => $channel->type ? $channel->type->id : null,
                ];
            }

            $this->storeChannelData($dataCollection, $channel);

            if (!empty($dataCollection[$channel->id]['openinghours'])) {
                continue;
            }

            if (!$this->includeIsOpenNow) {
                $dayInterval = \DateInterval::createFromDateString('1 day');
                $datePeriod = new \DatePeriod($this->start, $dayInterval, $this->end);

                foreach ($datePeriod as $day) {
                    $dataCollection[$channel->id]['openinghours'][] = new DayInfo($day);
                }
            }
        }

        usort($dataCollection[$channel->id]['openinghours'], fn (DayInfo $a, DayInfo $b) => $a->date > $b->date ? 1 : -1);

        return array_values($dataCollection);
    }

    /**
     * @param array $dataCollection
     * @param Channel $channel
     */
    private function storeChannelData(array &$dataCollection, Channel $channel)
    {
        $ohCollection = $channel->openinghours()
            ->where('start_date', '<=', $this->end->toDateString())
            ->where('end_date', '>=', $this->start->toDateString())
            ->get();

        if (!$this->includeIsOpenNow) {
            $dataCollection[$channel->id]['openinghours'] = [];
        }

        /** @var \App\Models\Openinghours $openinghours */
        foreach ($ohCollection as $openinghours) {
            // Copy the calendar start and end
            $calendarBegin = new Carbon($openinghours->start_date);

            if ($this->start > $openinghours->start_date) {
                $calendarBegin = clone $this->start;
            }
            $calendarEnd = new Carbon($openinghours->end_date);
            if ($this->end < $openinghours->end_date) {
                $calendarEnd = clone $this->end;
            }

            $dayInterval = \DateInterval::createFromDateString('1 day');
            $datePeriod = new \DatePeriod($calendarBegin->startOfDay(), $dayInterval, $calendarEnd->endOfDay());

            $ical = $openinghours->ical();
            $ical->createIcalString($calendarBegin, $calendarEnd);

            if (!$this->includeIsOpenNow) {
                $newOpeninghours = array_values($ical->getPeriodInfo($datePeriod, $this->from, $this->until));
                $mergedOpeninghours = array_merge($dataCollection[$channel->id]['openinghours'], $newOpeninghours);
                $dataCollection[$channel->id]['openinghours'] = $mergedOpeninghours;
            }

            if ($this->includeIsOpenNow) {
                $open = $ical->getOpenAt($this->start);
                $label = $open ? trans('openinghourApi.OPEN') : trans('openinghourApi.CLOSED');
                $dataCollection[$channel->id]['openNow']['label'] = $label;
                $dataCollection[$channel->id]['openNow']['status'] = $open ? true : false;
            }
        }
    }

    /**
     * @param Collection $collection
     * @return string
     * @throws \Exception
     */
    public function transformJsonLdCollection(Collection $collection)
    {
        if (!$this->service || !($this->service instanceof Service)) {
            throw new \Exception("JSON-LD formatter needs a service instance of \App\Models\Service", 1);
        }
        RdfNamespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph = new Graph();
        $service = $graph->resource($this->service->uri, 'schema:Organization');

        $data = $this->getCollectionData($collection);

        // get a raw render for the week:
        // $channel id + days index in english
        // for each channel create an openinghours specification
        // where the channel URI is also set as some sort of context
        foreach ($data as $channelArr) {
            $channelSpecification = $graph->resource(createChannelUri($channelArr['channelId']), 'cv:Channel');
            $channelSpecification->addLiteral('schema:label', $channelArr['channel']);
            if (isset($channelArr['openNow'])) {
                $channelSpecification->addLiteral(
                    'schema:isOpenNow',
                    ($channelArr['openNow']['status']) ? 'true' : 'false'
                );
            } else {
                $textDayInfo = $this->makeTextForDayInfo($channelArr['openinghours']);
                $channelSpecification->addLiteral('schema:openingHours', $textDayInfo);
            }
            $channelSpecification->addResource('cv:isOwnedBy', $service);
        }
        $serialiser = new JsonLdSerialiser();

        return $serialiser->serialise($graph, 'jsonld');
    }

    /**
     * @param $openinghours
     * @return string
     */
    private function makeTextForDayInfo($openinghours)
    {
        $text = '';
        foreach ($openinghours as $openinghoursObj) {
            $text .= trans('openinghourApi.day_' . date('w', strtotime($openinghoursObj->date))) . ' ';
            $text .= date($this->localeService->getDateFormat(), strtotime($openinghoursObj->date)) . ': ';
            if (!$openinghoursObj->open) {
                $text .= trans('openinghourApi.CLOSED');
                $text .= PHP_EOL;
                continue;
            }

            $hours = [];
            foreach ($openinghoursObj->hours as $hoursArr) {
                $hours[] = date($this->localeService->getTimeFormat(), strtotime($hoursArr['from'])) . "-" .
                    date($this->localeService->getTimeFormat(), strtotime($hoursArr['until']));
            }

            // implode hours[] with ', ' but make last ', '  =>  "and"
            // to result in for example 'HH:ii-HH:ii, HH:ii-HH:ii, HH:ii-HH:ii and HH:ii-HH:ii'
            // https://stackoverflow.com/a/8586179
            $last = array_slice($hours, -1);
            $first = implode(', ', array_slice($hours, 0, -1));
            $both = array_filter(array_merge([$first], $last), 'strlen');
            $text .= implode(' ' . trans('openinghourApi.AND') . ' ', $both);
            $text .= PHP_EOL;
        }
        $text .= PHP_EOL;

        return $text;
    }

    /**
     * @param Collection $channels
     * @return string
     */
    public function transformPlainTextCollection(Collection $channels)
    {
        $data = $this->getCollectionData($channels);

        $text = '';
        foreach ($data as $channelArr) {
            $text .= PHP_EOL . $channelArr['channel'] . ':' . PHP_EOL;
            for ($i = 0; $i < strlen($channelArr['channel']) + 1; $i++) {
                $text .= '=';
            }
            $text .= PHP_EOL;
            if (isset($channelArr['openNow'])) {
                $text .= $channelArr['openNow']['label'] . PHP_EOL;
                continue;
            }

            $text .= $this->makeTextForDayInfo($channelArr['openinghours']);
        }
        $text = rtrim($text, PHP_EOL);

        return $text;
    }

    /**
     * @param Collection $channels
     * @return string
     */
    public function transformHtmlTextCollection(Collection $channels)
    {
        $data = $this->getCollectionData($channels);

        try {
            $output = view(
                'api.openinghours.' . $this->calendarLength,
                [
                    'data' => $data,
                    'transformer' => $this,
                    'hasOneChannel' => $this->hasOneChannel,
                ]
            );
        } catch (\InvalidArgumentException $ex) {
            throw new NotAcceptableHttpException();
        }

        return $output;
    }

    /**
     * See if the next iteration is available
     *
     * @param $channelId
     * @return bool
     */
    public function hasNextIteration($channelId)
    {
        $channel = Channel::find($channelId);

        $referenceDate = clone $this->end;
        $referenceDate->addDay()->startOfDay();

        $ohCollection = $channel->openinghours()
            ->where('end_date', '>=', $referenceDate->toDateString())
            ->get();

        return count($ohCollection) > 0;
    }

    /**
     * See if the previous iteration is available
     *
     * @param $channelId
     * @return bool
     */
    public function hasPreviousIteration($channelId)
    {
        $channel = Channel::find($channelId);

        $referenceDate = clone $this->start;
        $referenceDate->subDay()->endOfDay();

        $ohCollection = $channel->openinghours()
            ->where('start_date', '<=', $referenceDate->toDateString())
            ->get();

        return count($ohCollection) > 0;
    }
}
