<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Openinghours extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'active', 'start_date', 'end_date', 'label', 'channel_id'
    ];

    public function calendars()
    {
        return $this->hasMany('App\Models\Calendar');
    }

    public function channel()
    {
        return $this->belongsTo('App\Models\Channel');
    }
}
