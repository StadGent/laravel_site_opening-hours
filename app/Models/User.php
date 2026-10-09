<?php

namespace App\Models;

use App\Mail\SendPasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements LaratrustUser
{
    use HasApiTokens;
    use HasRolesAndPermissions;
    use Notifiable;
    use HasFactory;

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
     * Check if the user is the super admin that may manage other admins
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->id === (int) config('auth.super_admin_id');
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
}
