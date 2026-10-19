<?php

namespace App\Mail;

use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Collection;

class SendInviteConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * Collection of services.
     *
     * @var Collection
     */
    public $services;

    /**
     * The invited role, shown when no services are given (global role).
     *
     * @var Role|null
     */
    public $role;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param Collection|null $services
     *  Collection of services.
     * @param Role|null $role
     * @return void
     */
    public function __construct(User $user, ?Collection $services = null, ?Role $role = null)
    {
        $this->user = $user;
        $this->services = $services ?? new Collection();
        $this->role = $role;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($this->services->isEmpty()
                ? 'U werd toegang verleend tot het openingsurenplatform'
                : 'U werd toegang verleend tot een dienst')
            ->view('auth.emails.invite');
        $params = [
            'services' => $this->services,
            'role' => $this->role,
        ];

        if ($this->user->token) {
            $params['actionUrl'] = url('register/confirm', $this->user->token);
        }
        // token is set to null in RegisterController completeRegistration
        $mail->with($params);

        return $mail;
    }
}
