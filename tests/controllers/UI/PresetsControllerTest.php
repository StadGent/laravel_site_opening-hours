<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class PresetsController extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui/presets';

    public function testHappyPath()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');
        $this->json('get', $this->apiUrl);
        $this->seeStatusCode(200);
    }
}
