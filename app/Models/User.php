<?php

namespace App\Models;

use App\Mail\SendPasswordResetNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laratrust\Traits\LaratrustUserTrait;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use LaratrustUserTrait;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['verified'];

    public function getVerifiedAttribute()
    {
        return !empty($this->password);
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'token',
    ];

    /**
     * Inject translated SendPasswordResetNotification
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this)->send(new SendPasswordResetNotification($token));
    }

    /**
     * Return roles for each service that the user belongs to
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function services()
    {
        return $this->belongsToMany('App\Models\Service', 'user_service_role', 'user_id', 'service_id')
          ->withPivot(['role_id']);
    }

    /**
     * @param $name
     * @param bool $requireAll
     *
     * @return mixed
     */
    public function hasRole($name, $requireAll = false) {
        return User::roles()->where('name', $name)->get()->pluck('name')->contains($name);
    }
}
