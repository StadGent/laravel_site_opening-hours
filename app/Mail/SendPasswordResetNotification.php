<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPasswordResetNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @param string $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS'))
            ->subject('Reset wachtwoord')
            ->view('auth.emails.reset')
            ->with([
                'actionUrl' => url('password/reset', $this->token),
            ]);
    }
}
