<?php

namespace App\Models;

use App\Jobs\DeleteLodOpeninghours;
use App\Jobs\UpdateLodOpeninghours;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Service;
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

    /**
     * hook into eloquent events
     */
    protected static function boot()
    {
        parent::boot();

        /**
         * check or vesta needs to be updated for this openinghours
         * updated openinghours that is stored in LOD
         */
        static::updated(function ($openinghours) {
            $channel = $openinghours->channel();
            dd($channel);
            $service = $channel->service();
            self::updateVestaWithOpeninghours($openinghours, $service);
            dispatch(new UpdateLodOpeninghours($service->id, $openinghours->id, $channel->id));
        });

        /**
         * check or vesta needs to be updated for this openinghours
         * delete openinghours that is stored in LOD
         */
        static::deleting(function ($openinghours) {
            $service = $$openinghours->channel()->service();

            self::updateVestaWithOpeninghours($openinghours, $service);
            dispatch(new DeleteLodOpeninghours($service->id, $openinghours->id));
        });
    }

    public function getActiveAttribute()
    {
        $today = Carbon::today()->toDateString();

        return $this->start_date <= $today && (empty($this->end_date) || $this->end_date >= $today);
    }

    /**
     * Update Vesta With Openinghours
     *
     * update the VESTA openinghours of the service,
     * ONLY WHEN that service is linked to a VESTA UID
     *
     * @param  Openinghours $openinghours 
     * @param  Service      $service      
     */
    protected static function updateVestaWithOpeninghours(Openinghours $openinghours, Service $service)
    {
        if ($openinghours->active) {
            if (!empty($service) && $service->source == 'vesta') {
                dispatch((new UpdateVestaOpeninghours($service->identifier, $service->id)));
            }
        }
    }

}
