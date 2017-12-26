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

    /**
     * @test
     * @group validation
     */
    public function testNoBeginOrEndGiveValidationError()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');

        $this->doRequest('get', $this->apiUrl);
        $this->seeStatusCode(400);
        $content = $this->decodeResponseJson();
        $this->assertEquals([
            'error' => [
                'code' => 'ValidationException',
                'message' => 'Paramters did not pass validation',
                'target' => 'parameters',
                'details' => [
                    0 => [
                        'code' => 'NotValidParameter',
                        'message' => 'The start date field is required.',
                        'target' => 'start_date',
                    ],
                    1 => [
                        'code' => 'NotValidParameter',
                        'message' => 'The end date field is required.',
                        'target' => 'end_date',
                    ],
                ],
            ],
        ], $content);
    }

    /**
     * @test
     */
    public function testHappyPath()
    {
        $this->requestsByUserWithRoleAndCheckStatusCode('admin', 'get', '?start_date=2017-01-01&end_date=2019-12-31', [], 200);
    }

    /**
     * assemble the path on the given params
     */
    protected function assemblePath($params)
    {
        return $this->apiUrl . '/' . $params;
    }
}
