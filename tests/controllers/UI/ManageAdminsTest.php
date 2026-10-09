<?php

namespace Tests\Controllers\UI;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;

/**
 * Only the super admin may grant or revoke the global Admin role
 */
class ManageAdminsTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var User
     */
    protected $superAdmin;

    /**
     * @var User
     */
    protected $otherAdmin;

    /**
     * setup for each test
     */
    public function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->superAdmin = User::where('name', 'adminuser')->first();
        config(['auth.super_admin_id' => $this->superAdmin->id]);

        $this->otherAdmin = User::factory()->create();
        $this->otherAdmin->addRole(Role::where('name', 'Admin')->first());
    }

    public function testSuperAdminCanMakeUserAdmin()
    {
        $owner = User::where('name', 'owneruser')->first();
        $this->assertCount(1, app('UserRepository')->getAllRolesForUser($owner->id));

        $this->actingAs($this->superAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $owner->id,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(200);
        $this->assertTrue($owner->fresh()->hasRole('Admin'));
        $this->assertCount(0, app('UserRepository')->getAllRolesForUser($owner->id));
    }

    public function testSuperAdminCanDemoteAdminToEditor()
    {
        $this->actingAs($this->superAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $this->otherAdmin->id,
            'role' => 'Editor',
        ]);

        $this->seeStatusCode(200);
        $this->assertFalse($this->otherAdmin->fresh()->hasRole('Admin'));
        $this->assertTrue($this->otherAdmin->fresh()->hasRole('Editor'));
    }

    public function testSuperAdminCanRemoveGlobalRoleOfAdmin()
    {
        $this->actingAs($this->superAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $this->otherAdmin->id,
            'role' => null,
        ]);

        $this->seeStatusCode(200);
        $this->assertFalse($this->otherAdmin->fresh()->hasRole('Admin'));
    }

    public function testSuperAdminCannotAlterHimself()
    {
        $this->actingAs($this->superAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $this->superAdmin->id,
            'role' => null,
        ]);

        $this->seeStatusCode(400);
        $this->assertTrue($this->superAdmin->fresh()->hasRole('Admin'));
    }

    public function testOtherAdminCannotMakeUserAdmin()
    {
        $editor = User::where('name', 'editoruser')->first();

        $this->actingAs($this->otherAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $editor->id,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(403);
        $this->assertFalse($editor->fresh()->hasRole('Admin'));
    }

    public function testOtherAdminCannotDemoteAdmin()
    {
        $this->actingAs($this->otherAdmin, 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $this->superAdmin->id,
            'role' => null,
        ]);

        $this->seeStatusCode(403);
        $this->assertTrue($this->superAdmin->fresh()->hasRole('Admin'));
    }

    public function testOwnerCannotMakeUserAdminThroughRoles()
    {
        $member = User::where('name', 'memberuser')->first();

        $this->actingAs(User::where('name', 'owneruser')->first(), 'api');
        $this->doRequest('PATCH', '/api/v1/ui/roles', [
            'user_id' => $member->id,
            'service_id' => 1,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(403);
        $this->assertFalse($member->fresh()->hasRole('Admin'));
    }

    public function testOwnerCannotInviteAdmin()
    {
        $newUser = User::factory()->make();

        $this->actingAs(User::where('name', 'owneruser')->first(), 'api');
        $this->doRequest('POST', '/api/v1/ui/inviteuser', [
            'email' => $newUser->email,
            'service_id' => 1,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(403);
        $this->assertNull(User::where('email', $newUser->email)->first());
    }

    public function testOtherAdminCannotInviteAdmin()
    {
        $newUser = User::factory()->make();

        $this->actingAs($this->otherAdmin, 'api');
        $this->doRequest('POST', '/api/v1/ui/inviteuser', [
            'email' => $newUser->email,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(403);
        $this->assertNull(User::where('email', $newUser->email)->first());
    }

    public function testSuperAdminCanInviteAdmin()
    {
        $newUser = User::factory()->make();

        $this->actingAs($this->superAdmin, 'api');
        $this->doRequest('POST', '/api/v1/ui/inviteuser', [
            'email' => $newUser->email,
            'role' => 'Admin',
        ]);

        $this->seeStatusCode(200);
        $this->assertTrue(User::where('email', $newUser->email)->first()->hasRole('Admin'));
    }
}
