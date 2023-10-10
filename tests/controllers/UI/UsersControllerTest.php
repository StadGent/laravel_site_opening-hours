<?php

namespace Tests\Controllers\UI;

use App\Mail\SendInviteConfirmation;
use App\Mail\SendRegisterConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;

class UsersControllerTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * setup for each test
     */
    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui';
    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['role_current_user', 'email_new_user', 'role', 'service_id']
     * @return array
     */
    public function requestValidationProvider()
    {
        return [
            ['admin', '', 'Owner', '1', 400], // no email
            ['admin', 'unknown', '', '1', 400], // no role
            ['admin', 'unknown', 'notARole', '1', 400], // unknown role
            ['admin', 'unknown', 'Member', '', 400], // no service for owner
            ['admin', 'admin@foo.bar', 'Member', '1', 401], // cannot alter himself
            ['admin', 'unknown', 'Owner', '', 400], // no service for member
            ['admin', 'unknown', 'Member', '54986468', 400], // unknown service

            ['admin', 'unknown', 'Admin', '', 200], // can add admin
            ['admin', 'unknown', 'Owner', '1', 200], // can add owner
            ['admin', 'unknown', 'Member', '1', 200], // can add member

            ['editor', 'unknown', 'Admin', '', 401], // cannot add admin
            ['editor', 'member@foo.bar', 'Admin', '', 401], // cannot make himself Admin
            ['editor', 'unknown', 'Owner', '1', 401], // cannot add owner
            ['editor', 'member@foo.bar', 'Owner', '1', 401], // cannot make himself Owner
            ['editor', 'unknown', 'Member', '1', 401], // cannot add member

            ['owner', 'unknown', 'Admin', '', 401], // cannot add admin
            ['owner', 'unknown', 'Owner', '5', 401], // cannot assign others to not owned service
            ['owner', 'owner@foo.bar', 'Owner', '5', 401], // cannot assign himself to not owned service
            ['owner', 'owner@foo.bar', 'Member', '5', 401], // cannot assign himself to not owned service
            ['owner', 'unknown', 'Owner', '1', 200], // can add owner to owned service
            ['owner', 'owner@foo.bar', 'Admin', '', 401], // cannot make himself Admin
            ['owner', 'owner@foo.bar', 'Member', '1', 401], // cannot alter himself
            ['owner', 'unknown', 'Member', '5', 401], // cannot add member to other service
            ['owner', 'unknown', 'Member', '1', 200], // can add member to owned service

            ['member', 'unknown', 'Admin', '', 401], // cannot add admin
            ['member', 'member@foo.bar', 'Admin', '', 401], // cannot make himself Admin
            ['member', 'unknown', 'Owner', '1', 401], // cannot add owner
            ['member', 'member@foo.bar', 'Owner', '1', 401], // cannot make himself Owner
            ['member', 'unknown', 'Member', '1', 401], // cannot add member
        ];
    }

    /**
     * @test
     * @group validation
     * @dataProvider requestValidationProvider
     */
    public function testInviteNewUserValidation($userRole, $email, $role, $serviceId, $statusCode)
    {
        $authUser = \App\Models\User::where('name', $userRole . 'user')->first();
        $this->actingAs($authUser, 'api');

        $newUser = User::factory()->make();

        if ($email) {
            $request['email'] = $email == 'unknown' ? $newUser->email : $email;
        }
        if ($role) {
            $request['role'] = $role;
        }
        if ($serviceId) {
            $request['service_id'] = $serviceId;
        }
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);
        $this->seeStatusCode($statusCode);
    }

    /**
     * @test
     */
    public function testWhenLinkedUserIsMadeAdminTheLinkToTheServicesAreRemoved()
    {
        $adminUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($adminUser, 'api');

        $ownerUser = \App\Models\User::where('name', 'owneruser')->first();

        $this->assertCount(1, app('UserRepository')->getAllRolesForUser($ownerUser->id));

        $request = [
            'email' => $ownerUser->email,
            'role' => 'Admin',
        ];
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);
        $this->assertCount(0, app('UserRepository')->getAllRolesForUser($ownerUser->id));
    }

    /**
     * @test
     */
    public function testWhenLinkedUserIsMadeEditorTheLinkToTheServicesAreNotRemoved()
    {
        $adminUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($adminUser, 'api');

        $ownerUser = \App\Models\User::where('name', 'owneruser')->first();

        $this->assertCount(1, app('UserRepository')->getAllRolesForUser($ownerUser->id));

        $request = [
            'email' => $ownerUser->email,
            'role' => 'Editor',
        ];
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);
        $this->assertCount(1, app('UserRepository')->getAllRolesForUser($ownerUser->id));
    }

    /**
     * @test
     */
    public function testWhenAdminIsMadeOwnerHeIsRemovedFromTheGlobalAdminRole()
    {
        Mail::fake();
        $adminUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($adminUser, 'api');

        $newUser = User::factory()->make();
        // lets make new user Admin
        $request['email'] = $newUser->email;
        $request['role'] = 'Admin';
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);
        $createdUser = $this->getContentStructureTested();
        $result = \DB::select(
            'SELECT * FROM role_user WHERE user_id = ?',
            [$createdUser['id']]
        );
        $this->assertCount(1, $result);

        // whoops little mistake: new user had to be Owner of Service 1
        $request['email'] = $createdUser['email'];
        $request['role'] = 'Owner';
        $request['service_id'] = '1';
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);

        $result = \DB::select(
            'SELECT * FROM role_user WHERE user_id = ?',
            [$createdUser['id']]
        );
        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function testWhenEditorIsMadeOwnerHeIsNotRemovedFromTheGlobalEditorRole()
    {
        Mail::fake();
        $adminUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($adminUser, 'api');

        $editorUser = \App\Models\User::where('name', 'editoruser')->first();

        // Editor user is also to be Owner of Service 1
        $request['email'] = $editorUser['email'];
        $request['role'] = 'Owner';
        $request['service_id'] = '1';
        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);

        $result = \DB::select(
            'SELECT * FROM role_user WHERE user_id = ?',
            [$editorUser['id']]
        );
        $this->assertCount(1, $result);
    }

    /**
     * @test
     */
    public function testAnInvitedNewUserGetsARegisterMailAndNotAnInviteMail()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');

        $newUser = User::factory()->make();

        $request = [
            'email' => $newUser->email,
            'role' => 'Member',
            'service_id' => 1,
        ];

        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);

        $savedUser = User::latest()->first();
        // Perform order shipping...

        Mail::assertSent(SendRegisterConfirmation::class, function ($mail) use ($savedUser) {
            return $mail->user->token === $savedUser->token;
        });

        // Mail::assertNotSent does not exist
        $this->assertFalse(
            Mail::sent(SendInviteConfirmation::class, function ($mail) use ($savedUser) {
                return $mail->user->token === $savedUser->token;
            })->count() > 1,
            "Mailable was sent."
        );
    }

    /**
     * @test
     */
    public function testAnInvitedKnownUserGetsAnInviteMailAndNotARegisterMail()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');

        $knownUser = \App\Models\User::find(2);

        $request = [
            'email' => $knownUser->email,
            'role' => 'Member',
            'service_id' => 2,
        ];

        $this->doRequest('POST', '/api/v1/ui/inviteuser', $request);

        Mail::assertSent(SendInviteConfirmation::class, function ($mail) use ($knownUser) {
            return $mail->user->token === $knownUser->token;
        });

        // Mail::assertNotSent does not exist
        $this->assertFalse(
            Mail::sent(SendRegisterConfirmation::class, function ($mail) use ($knownUser) {
                return $mail->user->token === $knownUser->token;
            })->count() > 1,
            "Mailable was sent."
        );
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
            ['unauth', 'get', 'users', [], 401], // index
            ['unauth', 'post', 'users', [], 405], // store
            ['unauth', 'get', 'users/1', [], 401], // show
            ['unauth', 'put', 'users/1', [], 405], // update (full)
            ['unauth', 'patch', 'users/1', [], 405], // update (partial)
            ['unauth', 'delete', 'users/1', [], 401], // destroy
            // admin user
            ['admin', 'get', 'users', [], 200], // index
            ['admin', 'get', 'services/1/users', [], 200], // index
            ['admin', 'post', 'users', $data, 405], // store
            ['admin', 'get', 'users/1', [], 200], // show
            ['admin', 'put', 'users/1', $data, 405], // update (full)
            ['admin', 'patch', 'users/1', $data, 405], // update (partial)
            ['admin', 'delete', 'users/2', [], 200], // destroy
            ['admin', 'delete', 'users/1', [], 401], // you can't delete yourself
            // editor user
            ['editor', 'get', 'users', [], 401], // index
            ['editor', 'get', 'services/1/users', [], 401], // index
            ['editor', 'post', 'users', $data, 405], // store
            ['editor', 'get', 'users/1', [], 401], // show
            ['editor', 'put', 'users/1', $data, 405], // update (full)
            ['editor', 'patch', 'users/1', $data, 405], // update (partial)
            ['editor', 'delete', 'users/2', [], 401], // destroy
            ['editor', 'delete', 'users/1', [], 401], // you can't delete yourself
            // owner user
            ['owner', 'get', 'users', [], 401], // index
            ['owner', 'get', 'services/1/users', [], 200], // getFromService
            ['owner', 'get', 'services/2/users', [], 401], // getFromService but not owned service
            ['owner', 'post', 'users', $data, 405], // store
            ['owner', 'get', 'users/1', [], 401], // show
            ['owner', 'put', 'users/1', $data, 405], // update (full)
            ['owner', 'patch', 'users/1', $data, 405], // update (partial)
            ['owner', 'delete', 'users/3', [], 401], // destroy
            // member user
            ['member', 'get', 'users', [], 401], // index
            ['member', 'get', 'services/1/users', [], 401], // getFromService
            ['member', 'get', 'services/2/users', [], 401], // getFromService but not owned service
            ['member', 'post', 'users', $data, 405], // store
            ['member', 'get', 'users/1', [], 401], // show
            ['member', 'put', 'users/1', $data, 405], // update (full)
            ['member', 'patch', 'users/1', $data, 405], // update (partial)
            ['member', 'delete', 'users/2', [], 401], // destroy
        ];
    }

    /**
     * @test
     * @group validation
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
