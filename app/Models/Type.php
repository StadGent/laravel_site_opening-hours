<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $hidden = ["created_at", "updated_at"];

    /**
     * Get all channels for a type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function channels()
    {
        return $this->belongsToMany('App\Models\Channel');
    }
}
