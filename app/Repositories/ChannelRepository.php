<?php

namespace App\Repositories;

use App\Models\Channel;

class ChannelRepository extends EloquentRepository
{
    public function __construct(Channel $channel)
    {
        parent::__construct($channel);
    }
}
