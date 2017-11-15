<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui/services';

    /**
     * @test
     */
    public function testFailOnDestroyWithWrongId()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');

        $this->doRequest('delete', '/api/v1/ui/services/2/channels/1');
        $this->seeStatusCode(422);
        $content = $this->decodeResponseJson();
        $this->assertEquals('Channel model is not found with given identifier', $content['error']['message']);
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
        return [
            //  unauth user
            ['unauth', 'get', '1/channels', [], '401'], // getFromService
            ['unauth', 'post', '1/channels', [], '401'], // store
            ['unauth', 'get', '1/channels/1', [], '405'], // show
            ['unauth', 'put', '1/channels/1', [], '405'], // update (full)
            ['unauth', 'patch', '1/channels/1', [], '405'], // update (partial)
            ['unauth', 'delete', '1/channels/1', [], '401'], // destroy
            // admin user
            ['admin', 'get', '1/channels', [], '200'], // getFromService
            ['admin', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], '200'], // store
            ['admin', 'get', '1/channels/1', [], '405'], // show
            ['admin', 'put', '1/channels/1', [], '405'], // update (full)
            ['admin', 'patch', '1/channels/1', [], '405'], // update (partial)
            ['admin', 'delete', '1/channels/1', [], '200'], // destroy
            ['admin', 'delete', '2/channels/1', [], '422'], // Model mismatch
            // owner user
            ['owner', 'get', '1/channels', [], '200'], // getFromService
            ['owner', 'get', '2/channels', [], '401'], // cannot see for not owned service
            ['owner', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], '200'], // store
            ['owner', 'post', '2/channels', ['label' => 'test', 'service_id' => 2], '401'], // cannot store on not owned service
            ['owner', 'get', '1/channels/1', [], '405'], // show
            ['owner', 'put', '1/channels/1', [], '405'], // update (full)
            ['owner', 'patch', '1/channels/1', [], '405'], // update (partial)
            ['owner', 'delete', '1/channels/1', [], '200'], // destroy
            ['owner', 'delete', '2/channels/6', [], '401'], // cannot destroy on not owned service
            // member user
            ['member', 'get', '1/channels', [], '200'], // getFromService
            ['member', 'get', '2/channels', [], '401'], // cannot see for not owned service
            ['member', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], '200'], // store
            ['member', 'post', '2/channels', ['label' => 'test', 'service_id' => 2], '401'], // cannot store on not membered service
            ['member', 'get', '1/channels/1', [], '405'], // show
            ['member', 'put', '1/channels/1', [], '405'], // update (full)
            ['member', 'patch', '1/channels/1', [], '405'], // update (partial)
            ['member', 'delete', '1/channels/1', [], '200'], // destroy
            ['member', 'delete', '2/channels/6', [], '401'], // cannot destroy on not membered service
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUIChannelRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
