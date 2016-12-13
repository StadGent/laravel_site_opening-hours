<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository extends EloquentRepository
{
    public function __construct(Event $event)
    {
        parent::__construct($event);
    }
}
