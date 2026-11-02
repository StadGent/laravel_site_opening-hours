<?php

namespace Tests\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterControllerTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function testValidTokenSetsPasswordAndClearsToken()
    {
        $token = Str::random(32);
        $user = User::create([
            'name' => 'Invited',
            'email' => 'invited-' . Str::random(8) . '@example.com',
            'password' => '',
            'token' => $token,
        ]);

        $this->call('POST', '/register/confirm/' . $token, [
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $user->refresh();
        $this->assertNull($user->token);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    /**
     * @test
     */
    public function testEmailInputCannotBeUsedToTakeOverAnotherAccount()
    {
        $victim = User::create([
            'name' => 'Victim',
            'email' => 'victim-' . Str::random(8) . '@example.com',
            'password' => Hash::make('original-password'),
        ]);

        $this->call('POST', '/register/confirm/' . Str::random(32), [
            'email' => $victim->email,
            'password' => 'attacker-password',
            'password_confirmation' => 'attacker-password',
        ]);

        $victim->refresh();
        $this->assertTrue(Hash::check('original-password', $victim->password));
        $this->assertFalse(Hash::check('attacker-password', $victim->password));
        $this->assertFalse(\Auth::check());
    }

    /**
     * @test
     */
    public function testValidTokenWithAnotherUsersEmailIsRejected()
    {
        $token = Str::random(32);
        $invited = User::create([
            'name' => 'Invited',
            'email' => 'invited-' . Str::random(8) . '@example.com',
            'password' => '',
            'token' => $token,
        ]);
        $other = User::create([
            'name' => 'Other',
            'email' => 'other-' . Str::random(8) . '@example.com',
            'password' => Hash::make('original-password'),
        ]);

        $this->call('POST', '/register/confirm/' . $token, [
            'email' => $other->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $invited->refresh();
        $other->refresh();
        $this->assertSame($token, $invited->token);
        $this->assertFalse(Hash::check('new-password', $invited->password));
        $this->assertTrue(Hash::check('original-password', $other->password));
        $this->assertFalse(\Auth::check());
    }

    /**
     * @test
     */
    public function testUsedTokenCannotBeReused()
    {
        $token = Str::random(32);
        $user = User::create([
            'name' => 'Invited',
            'email' => 'invited-' . Str::random(8) . '@example.com',
            'password' => '',
            'token' => $token,
        ]);

        $payload = [
            'email' => $user->email,
            'password' => 'first-password',
            'password_confirmation' => 'first-password',
        ];
        $this->call('POST', '/register/confirm/' . $token, $payload);
        \Auth::logout();

        $payload = [
            'email' => $user->email,
            'password' => 'second-password',
            'password_confirmation' => 'second-password',
        ];
        $this->call('POST', '/register/confirm/' . $token, $payload);

        $user->refresh();
        $this->assertTrue(Hash::check('first-password', $user->password));
    }

    /**
     * @test
     */
    public function testPublicRegistrationIsDisabled()
    {
        $this->call('GET', '/register');
        $this->assertResponseStatus(404);

        $this->call('POST', '/register', ['email' => 'self-registered@example.com']);
        $this->assertResponseStatus(404);
        $this->assertNull(User::where('email', 'self-registered@example.com')->first());
    }
}
