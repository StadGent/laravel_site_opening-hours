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
        'priority', 'summary', 'label',
    ];

    public function events()
    {
        return $this->hasMany('App\Models\Event');
    }

    public function openinghours()
    {
        return $this->belongsTo('App\Models\Openinghours');
    }
}
