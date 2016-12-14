<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'rrule', 'start_date', 'end_date', 'calendar_id', 'label'
    ];

    public function calendar()
    {
        return $this->belongsTo('App\Models\Calendar');
    }
}
