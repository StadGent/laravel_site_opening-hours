<?php

namespace App\Mail;

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
     * Create a new message instance.
     *
     * @param User $user
     * @param array|Collection $services
     *  Collection of services.
     * @return void
     */
    public function __construct(User $user, Collection $services = null)
    {
        $this->user = $user;
        $this->services = $services;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->from(env('MAIL_FROM_ADDRESS'))
            ->subject('U werd toegang verleend tot een dienst')
            ->view('auth.emails.invite');
        $params = [
            'services' => $this->services
        ];

        if ($this->user->token) {
            $params['actionUrl'] = url('register/confirm', $this->user->token);
        }
        // token is set to null in RegisterController completeRegistration
        $mail->with($params);

        return $mail;
    }
}
