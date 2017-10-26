<?php

namespace Tests\Controllers\Auth;

use App\Mail\SendPasswordResetNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ResetPasswordControllerTest extends \TestCase
{
    use DatabaseTransactions;
 

    /**
     * @test
     */
    public function testAPasswordResetGetsAMail()
    {
        Mail::fake();

        $user = User::all()->first();
        $response = $this->json(
            'POST',
            '/password/email',
            ['email' => $user->email]
        );

        // Perform order shipping...

        $record =  DB::table('password_resets')->select('token')->where('email', $user->email)->first();


        Mail::assertSent(SendPasswordResetNotification::class, function ($mail) use ($record) {
            return $mail->token === $record->token;
        });
    }
}
