<?php

namespace Tests\Controllers\Auth;

use App\Mail\SendPasswordResetNotification;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ResetPasswordControllerTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    protected $hasher;

    /**
     * setup for each test
     */
    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->hasher = $this->app->make(Hasher::class);
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
        $hasher = $this->hasher;
        Mail::assertSent(SendPasswordResetNotification::class, function ($mail) use ($record, $hasher) {
            return $hasher->check($mail->token, $record->token);
        });
    }
}
