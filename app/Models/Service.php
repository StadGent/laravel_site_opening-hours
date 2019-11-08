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
        'description',
        'draft',
        'identifier',
        'label',
        'source',
        'uri',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'countChannels',
    ];

    /**
     * Child Objects Channel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channels()
    {
        return $this->hasMany('App\Models\Channel')->orderBy('weight')->orderBy('id');
    }

    /**
     * Return roles for each service that the user belongs to
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_service_role', 'service_id', 'user_id')
            ->withPivot('role_id');
    }

    /**
     * @todo try to find a ORM solution for this
     * relation = $service->user->role (1 role per user for this service)
     * @return mixed
     */
    public function usersWithRole()
    {
        $roles = \App\Models\Role::all();
        $users = $this->users;
        foreach ($users as $user) {
            $user->role = $roles->find($user->pivot->role_id);
        }

        return $users;
    }

    /**
     * Get number of channels on the Service
     *
     * @return integer
     */
    public function getCountChannelsAttribute()
    {
        return $this->channels()->count();
    }
}
