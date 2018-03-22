<?php

namespace App\Models;

use App\Models\Ical;
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
     * @var ICal
     */
    private $iCal = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'active', 'start_date', 'end_date', 'label', 'channel_id',
    ];

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['channel'];

    /**
     * Child Objects Calendar
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calendars()
    {
        return $this->hasMany('App\Models\Calendar');
    }

    /**
     * Parent Object Channel
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongsTo('App\Models\Channel');
    }

    /**
     * @return boolean
     */
    public function getActiveAttribute()
    {
        $today = Carbon::today()->toDateString();

        return $this->start_date <= $today && (empty($this->end_date) || $this->end_date >= $today);
    }

    /**
     * Bind ICal with this->calendar collection
     *
     * @return Ical
     */
    public function ical()
    {
        if ($this->iCal === null) {
            $this->iCal = new Ical($this->calendars);
        }

        return $this->iCal;
    }
}
