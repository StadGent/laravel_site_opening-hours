<?php

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Openinghours;
use Carbon\Carbon;

class ChannelRepository extends EloquentRepository
{
    public function __construct(Channel $channel)
    {
        parent::__construct($channel);
    }

    public function getByOpeninghoursId($openinghoursId)
    {
        return Openinghours::find($openinghoursId)->channel;
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
