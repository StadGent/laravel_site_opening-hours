<?php

namespace App\Services;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
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
     * ServiceModel in scope
     *
     * @var App\Models\Service
     */
    private $serviceModel;

    /**
     * ChannelModel in scope
     *
     * @var \App\Models\Channel
     */
    private $channelModel;

    /**
     * Computed data
     *
     * @var array
     */
    private $data;

    /**
     * Compute or the channel(s) are open or closed on this moment.
     *
     * @return OpeninghoursService  $this
     */
    public function isOpenNow()
    {
        if (!isset($this->serviceModel)) {
            throw new \Exception('Set Service Model to calculate if a service', 1);
        }
        $start = new Carbon(); //('2017-09-14 22:00:00');
        $end   = $start->copy()->addMinute();
        $this->collectData($start, $end);

        /**
         * @todo this logic needs to move to the formatter
         */
        foreach ($this->data as $channelName => &$channelData) {
            if (!$channelData ||
                !isset(array_values($channelData)[0]) ||
                !isset(array_values($channelData)[0]['OH'])) {
                $channelData = null;
                continue;
            }
            $tmpDateData = array_values($channelData)[0];
            $channelData = strpos('Gesloten', $tmpDateData['OH'][0]) !== false ? trans('openinghourApi.CLOSED') : trans('openinghourApi.OPEN');
        }

        return $this;
    }

    /**
     * Compute or the channel(s) have openinghours or are closed for a given day.
     *
     * @param  Carbon|null $date    date to see the data from
     * @return OpeninghoursService  $this
     */
    public function isOpenOnDay(Carbon $start = null)
    {
        if (!isset($this->serviceModel)) {
            throw new \Exception('Set Service Model to calculate if a service is open', 1);
        }

        if (!$start) {
            $start = Carbon::today();
        }
        $end = $start->copy()->endOfDay();
        $this->collectData($start, $end);

        /**
         * @todo this logic needs to move to the formatter
         */
        foreach ($this->data as $channelName => &$channelData) {
            if (!$channelData ||
                !isset(array_values($channelData)[0]) ||
                !isset(array_values($channelData)[0]['OH'])) {
                $channelData = null;
                continue;
            }
            $tmpDateData = array_values($channelData)[0];
            $channelData = strpos('Gesloten', $tmpDateData['OH'][0]) !== false ? trans('openinghourApi.CLOSED') : implode($tmpDateData['OH'], ', ');
        }

        return $this;
    }

    /**
     * Compute for the next seven days or the channel(s) have openinghours or are closed
     *
     * @return OpeninghoursService  $this
     */
    public function isOpenForNextSevenDays()
    {
        if (!isset($this->serviceModel)) {
            throw new \Exception('Set Service Model to calculate if a service is open', 1);
        }

        $start = Carbon::now()->startOfDay();
        $end   = $start->copy()->addWeek()->subDay()->endOfDay();
        $this->collectData($start, $end);

        /**
         * @todo this logic needs to move to the formatter
         */
        foreach ($this->data as $channelName => &$channelData) {
            if (!$channelData ||
                !isset(array_values($channelData)[0]) ||
                !isset(array_values($channelData)[0]['OH'])) {
                $channelData = null;
                continue;
            }

            foreach ($channelData as $date => &$openhours) {
                $tmpData   = strpos('Gesloten', $openhours['OH'][0]) !== false ? trans('openinghourApi.CLOSED') : implode($openhours['OH'], ', ');
                $openhours = $openhours['date']['dayOfWeek'] . ' ' . $tmpData;
            }
        }

        return $this;
    }

    /**
     * Compute for a full week or the channel(s) have openinghours or are closed based on a given date
     *
     * @param  Carbon|null $date    date that falls into the requested week
     * @return OpeninghoursService  $this
     */
    public function isOpenForFullWeek(Carbon $date = null)
    {
        if (!isset($this->serviceModel)) {
            throw new \Exception('Set Service Model to calculate if a service is open', 1);
        }

        if (!$date) {
            $date = Carbon::today();
        }

        $start = $date->startOfWeek();
        $end   = $start->copy()->addWeek()->subDay()->endOfDay();
        $this->collectData($start, $end);

        /**
         * @todo this logic needs to move to the formatter
         */
        foreach ($this->data as $channelName => &$channelData) {
            if (!$channelData ||
                !isset(array_values($channelData)[0]) ||
                !isset(array_values($channelData)[0]['OH'])) {
                $channelData = null;
                continue;
            }

            foreach ($channelData as $date => &$openhours) {

                $tmpDateData = array_values($channelData)[0];
                //unset($channelData);

                $tmpData   = strpos('Gesloten', $openhours['OH'][0]) !== false ? trans('openinghourApi.CLOSED') : implode($openhours['OH'], ', ');
                $openhours = $openhours['date']['dayOfWeek'] . ' ' . $tmpData;
            }
        }

        return $this;
    }

    /**
     * Collect for the channel(s) the openingshours per day between a start and end date
     *
     * @param  Carbon $start        start date for calculations
     * @param  Carbon $end          end date for calculations
     * @return OpeninghoursService  $this
     */
    protected function collectData(Carbon $start, Carbon $end)
    {
        foreach ($this->data as $channelLabel => &$value) {
            $value   = null;
            $channel = Channel::where(['label' => $channelLabel])->first();
            if (!$channel->openinghours()->count()) {
                continue;
            }

            $value = [];
            // mutliple openinghour models possible if start + end extend over limits of model (openhours of months, endings on new year ...)
            $openinghoursCol = $channel->openinghours()
                ->where('start_date', '<=', $end->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->get();
            foreach ($openinghoursCol as $openinghours) {
                // addapt begin and end to dates of openinghours
                $calendarBegin = ($start > $openinghours->start_date) ? clone $start : new Carbon($openinghours->start_date);
                $calendarEnd   = ($end < $openinghours->end_date) ? clone $end : new Carbon($openinghours->end_date);
                $calendars     = $openinghours->calendars()
                    ->orderBy('priority', 'asc')
                    ->get();

                foreach ($calendars as $calendar) {
                    $ical         = app('ICalService')->createIcalFromCalendar($calendar, $calendarBegin, $calendarEnd);
                    $dateInterval = \DateInterval::createFromDateString('1 day');
                    $datePeriod   = new \DatePeriod($calendarBegin, $dateInterval, $calendarEnd);

                    foreach ($datePeriod as $day) {
                        $date = [
                            'day'       => $day->day,
                            'month'     => $day->month,
                            'year'      => $day->year,
                            'dayInWeek' => $day->dayOfWeek,
                            'dayOfWeek' => trans('openinghourApi.day_' . $day->dayOfWeek),
                        ];

                        $key                 = $day->toDateString();
                        $value[$key]['date'] = $date;
                        $dayInfo             = app('ICalService')->extractDayInfo($ical, $day, $day);

                        $value[$key]['OH'] = ['Gesloten'];
                        if (!empty($dayInfo)) {
                            $value[$key]['OH'] = $calendar->closinghours ? ['Gesloten'] : $dayInfo;
                        }
                    }
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
     * @return void
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

    /**
     * Setter App\Models\Service
     *
     * + reset $this->channelModel
     * + reset $this->data with channels of the Service as default;
     *
     * @param Service $serviceModel [description]
     */
    public function setServiceModel(Service $serviceModel)
    {
        $this->serviceModel = $serviceModel;
        $this->channelModel = null;
        $this->data         = array_fill_keys($this->serviceModel->channels()->pluck('label')->all(), null);

        return $this;
    }

    /**
     * Setter  App\Models\Channel
     *
     * + reset $this->data with this channel;
     *
     * @param Channel $channelModel [description]
     */
    public function setChannelModel(Channel $channelModel)
    {

        $this->channelModel = $channelModel;
        $this->data         = [$this->channelModel->label => null];

        return $this;
    }

}
