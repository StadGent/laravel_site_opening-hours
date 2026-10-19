<?php

namespace Tests\Mail;

use App\Mail\SendInviteConfirmation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SendInviteConfirmationTest extends \BrowserKitTestCase
{
    /**
     * @var User
     */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        config(['mail.from.address' => 'noreply@example.com']);

        $this->user = new User(['email' => 'new@example.com']);
        $this->user->token = 'token';
    }

    public function testItMentionsTheGlobalRoleWhenThereAreNoServices()
    {
        $role = new Role(['display_name' => 'Admin']);
        $mail = new SendInviteConfirmation($this->user, new Collection(), $role);

        $mail->assertSeeInHtml('de globale rol "Admin"', false);
        $mail->assertDontSeeInHtml('de dienst ""', false);
        $mail->assertHasSubject('U werd toegang verleend tot het openingsurenplatform');
    }

    public function testItNamesTheSingleService()
    {
        $mail = new SendInviteConfirmation($this->user, $this->services(['Cultuurdienst']));

        $mail->assertSeeInHtml('de dienst "Cultuurdienst"', false);
        $mail->assertSeeInHtml('toegang te krijgen tot het openingsurenplatform.', false);
    }

    public function testItListsMultipleServicesWithoutDumpingTheLastOne()
    {
        $mail = new SendInviteConfirmation($this->user, $this->services(['Dienst A', 'Dienst B']));

        $mail->assertSeeInHtml('<li>Dienst A</li>', false);
        $mail->assertSeeInHtml('<li>Dienst B</li>', false);
        $mail->assertSeeInHtml('toegang te krijgen tot het openingsurenplatform.', false);
        $mail->assertDontSeeInHtml('&quot;label&quot;', false);
    }

    public function testItUsesTheConfiguredSender()
    {
        $mail = new SendInviteConfirmation($this->user, $this->services(['Dienst A']));

        $this->assertTrue($mail->build()->hasFrom('noreply@example.com'));
    }

    /**
     * @param string[] $labels
     * @return Collection
     */
    private function services(array $labels): Collection
    {
        return new Collection(array_map(
            fn (string $label) => (new Service())->forceFill(['label' => $label]),
            $labels
        ));
    }
}
