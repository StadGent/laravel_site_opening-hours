<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'priority', 'summary', 'label', 'openinghours_id', 'closinghours',
    ];

    /**
     * Child Objects Event
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany('App\Models\Event');
    }

    /**
     * Parent Object Openinghours
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function openinghours()
    {
        return $this->belongsTo('App\Models\Openinghours');
    }
}
