<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultCalendar extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'default_calendars';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label'
    ];

    /**
     * Child Objects Event
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany('App\Models\DefaultEvent', 'calendar_id');
    }
}
