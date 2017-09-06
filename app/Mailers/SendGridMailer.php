<?php

namespace App\Mailers;

use Illuminate\Contracts\Mail\Mailer;
use SendGrid;
use SendGrid\Email;

class SendGridMailer
{
    /**
     * The Laravel Mailer instance.
     *
     * @var Mailer
     */
    protected $mailer;

    /**
     * The sender of the email.
     *
     * @var string
     */
    protected $from = '';

    /**
     * The recipient of the email.
     *
     * @var string
     */
    protected $to;

    /**
     * The view for the email.
     *
     * @var string
     */
    protected $view;

    /**
     * The data associated with the view for the email.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Create a new app mailer instance.
     *
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
        $this->from = env('ADMIN_EMAIL');

        if (empty($this->from)) {
            throw new \Exception('No admin email has been set for the application.');
        }
    }

    /**
     * Deliver a password reset link.
     *
     * @param  string $email
     * @param  string $token
     * @return void
     */
    public function sendResetLinkEmail($email, $token)
    {
        $this->to = $email;
        $this->view = 'auth.emails.reset';
        $this->data = [];
        $this->data['email'] = $email;
        $this->data['token'] = $token;
        $this->subject = 'Reset wachtwoord';

        return $this->deliver();
    }

    /**
     * Deliver the email confirmation.
     *
     * @param  string $email
     * @param  string $token
     * @return void
     */
    public function sendEmailConfirmationTo($email, $token)
    {
        $this->to = $email;
        $this->view = 'auth.emails.confirm';
        $this->data = [];
        $this->data['email'] = $email;
        $this->data['token'] = $token;
        $this->subject = 'Voltooi uw registratie';
        return $this->deliver();
    }

    /**
     * Deliver the email.
     *
     * @return void
     */
    public function deliver()
    {
        if (env('APP_ENV') == 'production') {
            $sendgrid = new SendGrid(env('SENDGRID_KEY'));
            $email = new Email();

            $html = view($this->view)
                ->with($this->data)
                ->render();

            $email->addTo($this->to)
                ->setFrom($this->from)
                ->setSubject($this->subject)
                ->setHtml($html);

            return $sendgrid->send($email);
        } else {
            \Log::info(
                'A mail would be sent to ' . $this->to . ' about ' . $this->subject,
                [(array)$this]
            );

            \Log::info(view($this->view)
                ->with($this->data)
                ->render());
        }
    }
}
