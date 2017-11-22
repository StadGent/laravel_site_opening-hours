<?php

namespace App\Http\Transformers;

use App\Models\Channel;
use App\Models\DayInfo;
use App\Models\Service;
use App\Services\LocaleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use EasyRdf_Serialiser_JsonLd as JsonLdSerialiser;

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

    private $service;

    private $localeService;

    private $includeIsOpenNow;

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
                ];
            }

            $this->storeChannelData($dataCollection, $channel);

            if (!empty($dataCollection[$channel->id]['openinghours'])) {
                continue;
            }

            $dayInterval = \DateInterval::createFromDateString('1 day');

            $datePeriod = new \DatePeriod($this->start, $dayInterval, $this->end);

            foreach ($datePeriod as $day) {
                if (!$this->includeIsOpenNow) {
                    $dataCollection[$channel->id]['openinghours'][] = new DayInfo($day);
                }
            }
        }

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
            ->where('active', '=', 1)
            ->get();

        if (!$this->includeIsOpenNow) {
            $dataCollection[$channel->id]['openinghours'] = [];
        }

        foreach ($ohCollection as $openinghours) {
            // addapt begin and end to dates of openinghours
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
                $newOpeninghours = array_values($ical->getPeriodInfo($datePeriod));
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
    public function transformJsonCollection(Collection $channels)
    {
        $data = $this->getCollectionData($channels);
        return json_encode($data);
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
        \EasyRdf_Namespace::set('cv', 'http://data.europa.eu/m8g/');

        $graph = new \EasyRdf_Graph();
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

        $formattedSchedule = '<div vocab="http://schema.org/" typeof="Library">';

        foreach ($data as $channelArr) {
            $formattedSchedule .= "<h1>" . $channelArr['channel'] . "</h1>";

            if (isset($channelArr['openNow'])) {
                $formattedSchedule .= "<div>" . $channelArr['openNow']['label'] . "</div>";
                continue;
            }

            foreach ($channelArr['openinghours'] as $ohObj) {
                $formattedSchedule .= '<div property="openingHoursSpecification" typeof="OpeningHoursSpecification">';
                $formattedSchedule .= '<time property="validFrom validThrough" datetime="';
                $formattedSchedule .= date('Y-m-d', strtotime($ohObj->date)) . '">';
                $formattedSchedule .= date($this->localeService->getDateFormat(), strtotime($ohObj->date));
                $formattedSchedule .= '</time>: ';
                if (!$ohObj->open) {
                    $formattedSchedule .= '<time property="closes" datetime="' .
                        date('Y-m-d', strtotime($ohObj->date)) . '">' .
                        trans('openinghourApi.CLOSED') .
                        '</time></div>';
                    continue;
                }

                foreach ($ohObj->hours as $hoursObj) {
                    $formattedSchedule .= ' ' . trans('openinghourApi.FROM_HOUR') . ' ' .
                        '<time property="opens" content="' . date('H:i:s', strtotime($hoursObj['from'])) . '">' .
                        date($this->localeService->getTimeFormat(), strtotime($hoursObj['from'])) .
                        '</time> ' .
                        trans('openinghourApi.UNTIL_HOUR') . ' ' .
                        '<time property="closes" content="' . date('H:i:s', strtotime($hoursObj['until'])) . '">' .
                        date($this->localeService->getTimeFormat(), strtotime($hoursObj['until'])) .
                        '</time> ';
                }
                $formattedSchedule .= "</div>";
            }
        }
        $formattedSchedule .= '</div>';

        return $formattedSchedule;
    }
}
