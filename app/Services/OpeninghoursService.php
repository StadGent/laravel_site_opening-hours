<?php

namespace App\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Calendar;
use App\Models\Channel;
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
     *
     * @return OpeninghoursService  $this
     */
    public function isOpenNow(Service $service, Channel $channel = null)
    {
        $start = Carbon::now();
        $end = $start->copy()->addMinute();
        $this->collectData($start, $end, $service, $channel);

        foreach ($this->data as &$channelData) {
            $openNow = new \stdClass();
            $channelData->openNow = new \stdClass();
            $openNow->status = false;
            $openNow->label = trans('openinghourApi.CLOSED');

            if ($channelData->openinghours[0]->open) {
                $openNow->status = true;
                $openNow->label = trans('openinghourApi.OPEN');
            }

            unset($channelData->openinghours);
            $channelData->openNow = $openNow;
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
                $key = $day->toDateString();
                $hoursObj = new \stdClass();
                $hoursObj->date = $key;
                $hoursObj->open = null;
                $channelData->openinghours[$key] = $hoursObj;
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

        // mutliple openinghour models possible
        // if start + end extend over limits of model
        // (openhours of months, endings on new year ...)
        $openinghoursCollection = $channel->openinghours()
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($openinghoursCollection as $openinghours) {
            // addapt begin and end to dates of openinghours
            $calendarBegin = new Carbon($openinghours->start_date);
            if ($start > $openinghours->start_date) {
                $calendarBegin = clone $start;
            }
            $calendarEnd = new Carbon($openinghours->end_date);
            if ($end < $openinghours->end_date) {
                $calendarEnd = clone $end;
            }
            $calendars = $openinghours->calendars()
                ->orderBy('priority', 'asc')
                ->get();

            $datePeriod = new \DatePeriod($calendarBegin, $this->dayInterval, $calendarEnd);

            foreach ($calendars as $calendar) {
                $this->collectDataFromCalendar($hoursObj, $calendar, $calendarBegin, $calendarEnd, $datePeriod);
            }
            // set all days withing calendar range without value to open = false (for example weekends)
            foreach ($datePeriod as $day) {
                $key = $day->toDateString();
                if ($hoursObj[$key]->open === null) {
                    $hoursObj[$key]->open = false;
                    $hoursObj[$key]->hours = [];
                }
            }
        }
        // remove date keys that where needed for quick navigation
        $channelData->openinghours = array_values($hoursObj);

        return $this;
    }

    /**
     * Loop over dates of calender to collect the data
     *
     * When a more dominant calendar already filled in the date, no data will be collected
     * The current collecting of the ICal is very expensive so will be avoided if possible
     * It is done inside the loop of days, so it will only be called when sertain that it is needed
     *
     * @param $hoursObj
     * @param Calendar $calendar
     * @param Carbon $calendarBegin
     * @param Carbon $calendarEnd
     * @param \DatePeriod $datePeriod
     * @return OpeninghoursService  $this
     */
    private function collectDataFromCalendar($hoursObj, Calendar $calendar, Carbon $calendarBegin, Carbon $calendarEnd, \DatePeriod $datePeriod)
    {
        $ical = null;
        foreach ($datePeriod as $day) {
            $key = $day->toDateString();
            // if value isset => it is done by a prev exception calendar
            if ($hoursObj[$key]->open !== null) {
                continue;
            }
            // very slow => so only get the ical when required
            if (!$ical) {
                $ical = app('ICalService')->createIcalFromCalendar($calendar, $calendarBegin, $calendarEnd);
            }
            // extract dayinfo
            $dayInfo = app('ICalService')->extractDayInfo($ical, $day, $day);
            if (!empty($dayInfo)) {
                $hoursObj[$key]->open = false;
                $hoursObj[$key]->hours = [];
                if (!$calendar->closinghours) {
                    $hoursObj[$key]->open = true;
                    $hoursObj[$key]->hours = $dayInfo;
                }
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
