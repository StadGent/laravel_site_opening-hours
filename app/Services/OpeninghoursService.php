<?php

namespace App\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Channel;
use App\Models\DayInfo;
use App\Models\Ical;
use App\Models\Openinghours;
use App\Models\Service;
use Carbon\Carbon;

/**
 * Internal Business logic Service for Openinghours
 */
class OpeninghoursService
{
    /**
     * Computed data
     *
     * @var array
     */
    private $data;

    /**
     * @var DateInterval
     */
    private $dayInterval;

    /**
     * Singleton class instance.
     *
     * @var OpeninghoursService
     */
    private static $instance;

    /**
     * Private contructor for Singleton pattern
     */
    private function __construct()
    {
        $this->dayInterval = \DateInterval::createFromDateString('1 day');
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return OpeninghoursService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new OpeninghoursService();
        }
        self::$instance->serviceModel = null;

        return self::$instance;
    }

    /**
     * Compute or the channel(s) are open or closed on this moment.
     * Shortcut to avoid overhead in collectData() and collectDataForChannel() to get getDayInfo
     * and filter out only required data
     *
     * @return OpeninghoursService  $this
     */
    public function isOpenNow(Service $service, Channel $channel = null, $testDateTime = null)
    {
        $start = Carbon::now();
        // testing
        if ($testDateTime) {
            $start = new Carbon($testDateTime);
        }
        $end = $start->copy()->addMinute();

        if (!isset($service->id)) {
            throw new \Exception("Cannot get data without service model", 1);
        }

        $activeChannels = isset($channel->id) ? [$channel] : $service->channels;

        $this->data = [];

        foreach ($activeChannels as $currentChannel) {
            $channelData = new \stdClass();
            $this->data[$currentChannel->id] = $channelData;

            $channelData->channel = $currentChannel->label;
            $channelData->channelId = $currentChannel->id;
            $openNow = new \stdClass();
            $channelData->openNow = $openNow;

            $ohCollection = $currentChannel->openinghours()
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->where('active', '=', 1)
                ->get();

            foreach ($ohCollection as $openinghours) {
                // addapt begin and end to dates of openinghours
                $calendarBegin = new Carbon($openinghours->start_date);
                if ($start > $openinghours->start_date) {
                    $calendarBegin = clone $start;
                }
                $calendarEnd = new Carbon($openinghours->end_date);
                if ($end < $openinghours->end_date) {
                    $calendarEnd = clone $end;
                }
                // create Ical and collect data
                $ical = $openinghours->ical();
                $ical->createIcalString($calendarBegin, $calendarEnd);
                $dayInfo = $ical->getDayInfo($calendarBegin, true);
                // set results to endData
                $openNow->status = $dayInfo->open ? true : false;
                $openNow->label = $dayInfo->open ? trans('openinghourApi.OPEN') : trans('openinghourApi.CLOSED');
            }
        }

        return $this;
    }

    /**
     * Collect for the channel(s) the openingshours per day between a start and end date
     *
     * @param  Carbon       $start
     * @param  Carbon       $end
     * @param  Service      $service
     * @param  Channel|null $channel
     * @return OpeninghoursService  $this
     */
    public function collectData(Carbon $start, Carbon $end, Service $service, Channel $channel = null)
    {
        if (!isset($service->id)) {
            throw new \Exception("Cannot get data without service model", 1);
        }

        $activeChannels = isset($channel->id) ? [$channel] : $service->channels;

        $this->data = [];

        foreach ($activeChannels as $currentChannel) {
            $channelData = new \stdClass();
            $this->data[$currentChannel->id] = $channelData;

            $channelData->channel = $currentChannel->label;
            $channelData->channelId = $currentChannel->id;
            $channelData->openinghours = [];

            if (!$currentChannel->openinghours()->count()) {
                continue;
            }

            // fill up the requested dates not covered by the calendars
            $datePeriod = new \DatePeriod($start, $this->dayInterval, $end);
            foreach ($datePeriod as $day) {
                $channelData->openinghours[$day->toDateString()] = new DayInfo($day);
            }

            $this->collectDataForChannel($currentChannel, $start, $end);
        }

        return $this;
    }

    /**
     *  collect data for a channel
     *
     * @param  Channel $channel
     * @param  Carbon  $start
     * @param  Carbon  $end
     * @return OpeninghoursService  $this
     */
    private function collectDataForChannel(Channel $channel, Carbon $start, Carbon $end)
    {
        $channelData = $this->data[$channel->id];
        $hoursObj = $channelData->openinghours;

        $ohCollection = $channel->openinghours()
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($ohCollection as $openinghours) {
            // addapt begin and end to dates of openinghours
            $calendarBegin = new Carbon($openinghours->start_date);
            if ($start > $openinghours->start_date) {
                $calendarBegin = clone $start;
            }
            $calendarEnd = new Carbon($openinghours->end_date);
            if ($end < $openinghours->end_date) {
                $calendarEnd = clone $end;
            }

            $datePeriod = new \DatePeriod($calendarBegin, $this->dayInterval, $calendarEnd);

            $ical = $openinghours->ical();
            $ical->createIcalString($calendarBegin, $calendarEnd);
            foreach ($datePeriod as $day) {
                $channelData->openinghours[$day->toDateString()] = $ical->getDayInfo($day);
            }
        }

        return $this;
    }

    /**
     * Creat Jobs to sync data to external services
     *
     * Make job for VESTA update when given openinghours is active
     * and hase vesta source.
     * Make job update LOD or delete LOD
     *
     * @param  Openinghours $openinghours
     * @param  string       $type
     * @return OpeninghoursService  $this
     */
    public function makeSyncJobsForExternalServices(Openinghours $openinghours, $type)
    {
        if (!in_array($type, ['update', 'delete'])) {
            throw new \Exception('Define correct type of sync to external services', 1);
        }

        $channel = $openinghours->channel;
        $service = $channel->service;

        if ($openinghours->active) {
            if (!empty($service) && $service->source == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service->identifier, $service->id)));
            }
        }

        switch ($type) {
            case 'update':
                dispatch(new UpdateLodOpeninghours($service->id, $openinghours->id, $channel->id));
                break;
            case 'delete':
                dispatch(new DeleteLodOpeninghours($service->id, $openinghours->id));
                break;
        }

        return $this;
    }

    /**
     * Getter the collected data
     *
     * @return arrary
     */
    public function getData()
    {
        return $this->data;
    }
}
