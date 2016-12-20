<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uri', 'label', 'description'
    ];

    public function channels()
    {
        return $this->hasMany('App\Models\Channel');
    }

    /**
     * The roles that belong to the user.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_service_role', 'service_id', 'user_id');
    }
}
