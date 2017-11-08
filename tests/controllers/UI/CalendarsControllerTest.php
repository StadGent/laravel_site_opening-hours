<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalendarsControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/ui/calendars';

 

    /**
     * @test
     */
    public function testFailOnDestroyWithWrongId()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');

        $this->doRequest('delete', $this->apiUrl . '/' . '95868_684_not_anID');
        $this->seeStatusCode(400);
        $content = $this->decodeResponseJson();
        $this->assertEquals('De kalender werd niet verwijderd, er is iets foutgegaan.', $content['message']);
    }

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userRole', verb', 'uri', 'data', 'responce status'] // Resource controller action
     *
     * @return array
     */
    public function requestTypeProvider()
    {
        $data = [
            'priority' => '2',
            'label' => 'test',
            'openinghours_id' => '1',
        ];

        return [
            //  unauth user
            ['unauth', 'get', '', [], '405'], // index
            ['unauth', 'post', '', [], '401'], // store
            ['unauth', 'get', '1', [], '405'], // show
            ['unauth', 'put', '1', [], '401'], // update (full)
            ['unauth', 'patch', '1', [], '401'], // update (partial)
            ['unauth', 'delete', '1', [], '401'], // destroy
            // admin user
            ['admin', 'get', '', [], '405'], // index
            ['admin', 'post', '', $data, '200'], // store
            ['admin', 'get', '1', [], '405'], // show
            ['admin', 'put', '1', $data, '200'], // update (full)
            ['admin', 'patch', '1', $data, '200'], // update (partial)
            ['admin', 'delete', '1', [], '200'], // destroy
            // owner user
            ['owner', 'get', '', [], '405'], // index
            ['owner', 'post', '', $data, '200'], // store
            ['owner', 'get', '1', [], '405'], // show
            ['owner', 'put', '1', $data, '200'], // update (full)
            ['owner', 'patch', '1', $data, '200'], // update (partial)
            ['owner', 'delete', '1', [], '200'], // destroy
            // member user
            ['member', 'get', '', [], '405'], // index
            ['member', 'post', '', $data, '200'], // store
            ['member', 'get', '1', [], '405'], // show
            ['member', 'put', '1', $data, '200'], // update (full)
            ['member', 'patch', '1', $data, '200'], // update (partial)
            ['member', 'delete', '1', [], '200'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUICalendarsRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
