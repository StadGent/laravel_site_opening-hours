<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function getActiveAttribute()
    {
        $today = Carbon::now()->toDateString();

        return $this->start_date <= $today && (empty($this->end_date) || $this->end_date >= $today);
    }
}
