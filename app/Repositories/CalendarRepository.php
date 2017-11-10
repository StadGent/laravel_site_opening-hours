<?php

namespace App\Repositories;

use App\Models\Calendar;
use App\Models\Openinghours;

class CalendarRepository extends EloquentRepository
{

    public function __construct(Calendar $calendar)
    {
        parent::__construct($calendar);
    }

    public function getById($id)
    {
        $calendar = $this->model->where('id', $id)->with('events')->first();

        if (!empty($calendar)) {
            return $calendar->toArray();
        }

        return [];
    }

    /**
     * Remove the calendar and update the priority of his siblings.
     *
     * @param $modelId
     *
     * @return bool
     */
    public function delete($modelId)
    {
        $calendar = Calendar::find($modelId);
        $siblings = Openinghours::find($calendar->openinghours_id)
            ->calendars()
            ->get();

        foreach ($siblings as $sibling) {
            if ($sibling->priority < $calendar->priority) {
                $sibling->priority++;
                $sibling->save();
            }
        }

        return parent::delete($modelId);
    }
}
