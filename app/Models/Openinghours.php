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
            $newCalendar = Calendar::create($calendar);

            foreach (Calendar::find($calendar['id'])->events->toArray() as $event) {
                // Update period of the base openinghours.
                if ($newCalendar->priority === 0) {
                    $event = $this->adjustBaseEvent($event);
                }
                $event['calendar_id'] = $newCalendar->id;
                Event::create($event);
            }
        }

        return $this;
    }

    /**
     * @param $event
     *
     * @return mixed
     */
    private function adjustBaseEvent($event)
    {
        // In case of opening hours past midnight, end date is next day
        // we need to keep the difference in days between start and end date.
        try {
            // Get the difference in days.
            $startDate = new DateTime($event['start_date']);
            $endDate = new DateTime($event['end_date']);
            $startDate->setTime(0, 0, 0, 0);
            $endDate->setTime(0, 0, 0, 0);
            $daysDifference = $startDate->diff($endDate)->days;

            // Add the same difference ot the current start_date.
            $endDate = (new DateTime($this->start_date))
                ->add(new DateInterval('P' . $daysDifference . 'D'))
                ->format('Y-m-d');

            // Update date, keep time.
            $event['end_date'] = $endDate . ' ' .
                explode(' ', $event['end_date'])[1];
        } catch (Exception $e) {
            // Just in case some date value was malformed, keep the old behaviour.
            $event['end_date'] = $this->start_date . ' ' .
                explode(' ', $event['end_date'])[1];
        } finally {
            // Update date, keep time.
            $event['start_date'] = $this->start_date . ' ' .
                explode(' ', $event['start_date'])[1];
        }

        // Update until to end_date of the version.
        $event['until'] = $this->end_date;
        return $event;
    }
}
