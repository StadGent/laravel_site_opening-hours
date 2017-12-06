<?php

namespace Tests\Controllers\Auth;

use App\Mail\SendPasswordResetNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ResetPasswordControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * setup for each test
     */
    public function setup()
    {
        parent::setUp();
        Mail::fake();
    }

    /**
     * @test
     */
    public function testAPasswordResetGetsAMail()
    {
        $user = User::all()->first();
        $response = $this->json(
            'POST',
            '/password/email',
            ['email' => $user->email]
        );

        // Perform order shipping...
        $record = DB::table('password_resets')->select('token')->where('email', $user->email)->first();

        Mail::assertSent(SendPasswordResetNotification::class, function ($mail) use ($record) {
            return $mail->token === $record->token;
        });
    }
}
