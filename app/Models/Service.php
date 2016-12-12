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
}
