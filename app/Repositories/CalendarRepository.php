<?php

namespace App\Repositories;

use App\Models\Calendar;

class CalendarRepository extends EloquentRepository
{
    public function __construct(Calendar $calendar)
    {
        parent::__construct($calendar);
    }

    public function getById($id)
    {
        $calendar = $this->model->where('id', $id)->with('events')->first();

        if (! empty($calendar)) {
            return $calendar->toArray();
        }

        return [];
    }
}
