<?php

namespace App\Mail;

use App\Models\Service;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendInviteConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user, Service $service)
    {
        $this->user = $user;
        $this->service = $service;
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
            'service' => $this->service->label,
        ];

        if ($this->user->token) {
            $params['actionUrl'] = url('register/confirm', $this->user->token);
        }
        // token is set to null in RegisterController completeRegistration
        $mail->with($params);

        return $mail;
    }
}
