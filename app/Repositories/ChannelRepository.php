<?php

namespace App\Repositories;

use App\Models\Channel;
use App\Models\Openinghours;

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
}
