<?php

namespace App\Repositories;

use App\Models\Calendar;

class CalendarRepository extends EloquentRepository
{
    public function __construct(Calendar $calendar)
    {
        parent::__construct($calendar);
    }
}
