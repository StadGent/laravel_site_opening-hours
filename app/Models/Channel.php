<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label', 'service_id', 'type_id',
    ];

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['service'];

    /**
     * Child Objects Openinghours
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function openinghours()
    {
        return $this->hasMany('App\Models\Openinghours');
    }

    /**
     * Parent Object Service
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo('App\Models\Service');
    }

    /**
     * Channel type identifier
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo('App\Models\Type', 'type_id');
    }
}
