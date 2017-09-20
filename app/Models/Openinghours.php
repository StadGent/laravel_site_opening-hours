<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Openinghours extends Model
{
    /**
     * The table to store the openinghours in
     * @var string
     */
    protected $table = 'openinghours';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'active', 'start_date', 'end_date', 'label', 'channel_id',
    ];

    public function calendars()
    {
        return $this->hasMany('App\Models\Calendar');
    }

    public function channel()
    {
        return $this->belongsTo('App\Models\Channel');
    }

    public function getActiveAttribute()
    {
        $today = Carbon::today()->toDateString();

        return $this->start_date <= $today && (empty($this->end_date) || $this->end_date >= $today);
    }

}
