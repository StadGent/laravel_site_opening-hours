<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use DateInterval;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Openinghours extends Model
{

    /**
     * The table to store the openinghours in
     *
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
        'active',
        'start_date',
        'end_date',
        'label',
        'channel_id',
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
     * Child Objects Calendar
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function publishedCalendars()
    {
        // only fetch those calendars which are OK to be published
        return $this->hasMany('App\Models\Calendar')->where('published', true);
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
            $this->iCal = new Ical($this->publishedCalendars);
        }

        return $this->iCal;
    }

    /**
     * Copy calendars and events from another version.
     *
     * @param $originalVersion
     *
     * @return $this
     */
    public function copy($originalVersion)
    {
        foreach (Openinghours::find($originalVersion)->calendars->toArray() as $calendar) {
            $calendar['openinghours_id'] = $this->id;
            $calendar['published'] = false;
            $new_calendar = Calendar::create($calendar);

            foreach (Calendar::find($calendar['id'])->events->toArray() as $event) {
                // update period of the base openinghours
                if ($new_calendar->priority === 0) {

                    // in case of opening hours past midnight, end date is next day
                    // we need to keep the difference in days between start and end date
                    try {
                        // get the difference in days
                        $start_date = new DateTime($event['start_date']);
                        $end_date = new DateTime($event['end_date']);
                        $start_date->setTime(0, 0, 0, 0);
                        $end_date->setTime(0, 0, 0, 0);
                        $days_difference = $start_date->diff($end_date)->days;

                        // add the same difference ot the current start_date
                        $end_date = (new DateTime($this->start_date))
                            ->add(new DateInterval('P' . $days_difference . 'D'))
                            ->format('Y-m-d');

                        // update date, keep time
                        $event['end_date'] = $end_date . ' ' . explode(' ',
                                $event['end_date'])[1];
                    } catch (Exception $e) {
                        // just in case some date value was malformed, keep the old behaviour
                        $event['end_date'] = $this->start_date . ' ' . explode(' ',
                                $event['end_date'])[1];
                    } finally {
                        // update date, keep time
                        $event['start_date'] = $this->start_date . ' ' . explode(' ',
                                $event['start_date'])[1];
                    }

                    // update until to end_date of the version
                    $event['until'] = $this->end_date;
                }
                $event['calendar_id'] = $new_calendar->id;
                Event::create($event);
            }
        }

        return $this;
    }
}
