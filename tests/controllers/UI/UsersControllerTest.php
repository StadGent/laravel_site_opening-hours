<?php

namespace Tests\Controllers\UI;

use App\Mail\SendRegisterConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;

class UsersControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/ui/users';

    /**
     * @test
     */
    public function testAUserStoreGetsAMail()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');

        $newUser = factory(User::class)->make();

        Mail::fake();

        $request = [
            'email' => $newUser->email,
            'name' => $newUser->email,
            'text' => "newUser"];

        $call = $this->doRequest('POST', $this->apiUrl, $request);

        $savedUser = User::latest()->first();
        // Perform order shipping...

        Mail::assertSent(SendRegisterConfirmation::class, function ($mail) use ($savedUser) {
            return $mail->user->token === $savedUser->token;
        });
    }
}
