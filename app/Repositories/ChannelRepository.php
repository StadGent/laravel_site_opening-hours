<?php

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Openinghours;
use Carbon\Carbon;
use App\Models\Service;

class ChannelRepository extends EloquentRepository
{
    public function __construct(Channel $channel)
    {
        parent::__construct($channel);
    }

    public function getByOpeninghoursId($openinghoursId)
    {
        $openinghours = Openinghours::find($openinghoursId);

        if (! empty($openinghours)) {
            return $openinghours->channel;
        }

        return;
    }

    public function getByIdWithOpeninghours($channelId)
    {
        return $this->model->with('openinghours')->find($channelId);
    }

    /**
     * Return the channel by the service ID and the name of the channel
     *
     * @param  string $serviceUri
     * @param  string $name
     * @return array
     */
    public function getByName($serviceUri, $name)
    {
        $service = Service::where('uri', $serviceUri)->first();

        if (empty($service)) {
            return [];
        }

        return $this->model->where('service_id', $service['id'])->where('label', $name)->first();
    }

    /**
     * Return the channel with its related service
     *
     * @param  int   $channelId
     * @return array
     */
    public function getFullObjectById($channelId)
    {
        $channel = $this->model->with('service')->find($channelId);

        if (empty($channel)) {
            return [];
        }

        return $channel->toArray();
    }

    /**
     * Checks if a channel has openinghours for the given time interval
     *
     * @param  int     $channelId
     * @param  string  $start
     * @param  string  $end
     * @param  int     $id        The ID of an openinghours model of the channel, optional
     * @return boolean
     */
    public function hasOpeninghoursForInterval($channelId, $start, $end, $id = null)
    {
        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end = Carbon::createFromFormat('Y-m-d', $end);

        $openinghours = Openinghours::where('channel_id', $channelId)
                            ->where(function ($query) use ($start, $end) {
                                $query->whereBetween('start_date', [$start, $end])
                                        ->orWhereBetween('end_date', [$start, $end]);
                            });

        // Exclude the openinghours instance
        if (! empty($id)) {
            $openinghours->where('id', '!=', $id);
        }

        return ! empty($openinghours->first());
    }
}
