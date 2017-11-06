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
    public function testAnInvitedNewUserGetsAMail()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');

        $newUser = factory(User::class)->make();
        Mail::fake();

        $request = [
            'email' => $newUser->email,
            'role' => 'Member',
            'service_id' => 1,
            'text' => "newUser"];

        $call = $this->doRequest('POST', '/api/ui/inviteuser', $request);

        $savedUser = User::latest()->first();
        // Perform order shipping...

        Mail::assertSent(SendRegisterConfirmation::class, function ($mail) use ($savedUser) {
            return $mail->user->token === $savedUser->token;
        });
    }

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userRole', verb', 'uri', 'data', 'responce status'] // Resource controller action
     * @return array
     */
    public function requestTypeProvider()
    {
        $data = [
        ];

        return [
            //  unauth user
            ['unauth', 'get', '', [], '401'], // index
            ['unauth', 'post', '', [], '405'], // store
            ['unauth', 'get', '1', [], '401'], // show
            ['unauth', 'put', '1', [], '405'], // update (full)
            ['unauth', 'patch', '1', [], '405'], // update (partial)
            ['unauth', 'delete', '1', [], '401'], // destroy
            // admin user
            ['admin', 'get', '', [], '200'], // index
            ['admin', 'post', '', $data, '405'], // store
            ['admin', 'get', '1', [], '200'], // show
            ['admin', 'put', '1', $data, '405'], // update (full)
            ['admin', 'patch', '1', $data, '405'], // update (partial)
            ['admin', 'delete', '2', [], '200'], // destroy
            // owner user
            ['owner', 'get', '', [], '401'], // index
            ['owner', 'post', '', $data, '405'], // store
            ['owner', 'get', '1', [], '401'], // show
            ['owner', 'put', '1', $data, '405'], // update (full)
            ['owner', 'patch', '1', $data, '405'], // update (partial)
            ['owner', 'delete', '3', [], '401'], // destroy
            // member user
            ['member', 'get', '', [], '401'], // index
            ['member', 'post', '', $data, '405'], // store
            ['member', 'get', '1', [], '401'], // show
            ['member', 'put', '1', $data, '405'], // update (full)
            ['member', 'patch', '1', $data, '405'], // update (partial)
            ['member', 'delete', '2', [], '401'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUIUserRequests($userRole, $verb, $pathArg, $data, $statusCode)
    {
        $this->requestsByUserWithRoleAndCheckStatusCode($userRole, $verb, $pathArg, $data, $statusCode);
    }

    /**
     * assemble the path on the given params
     */
    protected function assemblePath($params)
    {
        return $this->apiUrl . '/' . $params;
    }
}
