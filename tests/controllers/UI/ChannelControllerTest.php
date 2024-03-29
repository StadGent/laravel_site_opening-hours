<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChannelControllerTest extends \BrowserKitTestCase
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
        $content = $this->response->json();
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
            ['unauth', 'get', '1/channels', [], 401], // getFromService
            ['unauth', 'post', '1/channels', [], 401], // store
            ['unauth', 'get', '1/channels/1', [], 405], // show
            ['unauth', 'put', '1/channels/1', [], 401], // update (full)
            ['unauth', 'patch', '1/channels/1', [], 405], // update (partial)
            ['unauth', 'delete', '1/channels/1', [], 401], // destroy
            // admin user
            ['admin', 'get', '1/channels', [], 200], // getFromService
            ['admin', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], 200], // store
            ['admin', 'get', '1/channels/1', [], 405], // show
            ['admin', 'put', '1/channels/1', ['label' => 'updated label'], 200], // update (partial)
            ['admin', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => null], 200], // update (full)
            ['admin', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => 1], 200], // update (full)
            ['admin', 'patch', '1/channels/1', [], 405], // update (partial)
            ['admin', 'delete', '1/channels/1', [], 200], // destroy
            ['admin', 'delete', '2/channels/1', [], 422], // Model mismatch
            // editor user
            ['editor', 'get', '1/channels', [], 200], // getFromService
            ['editor', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], 200], // store
            ['editor', 'get', '1/channels/1', [], 405], // show
            ['editor', 'put', '1/channels/1', ['label' => 'updated label'], 200], // update (partial)
            ['editor', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => null], 200], // update (full)
            ['editor', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => 1], 200], // update (full)
            ['editor', 'patch', '1/channels/1', [], 405], // update (partial)
            ['editor', 'delete', '1/channels/1', [], 401], // destroy
            ['editor', 'delete', '2/channels/1', [], 401], // Model mismatch
            // owner user
            ['owner', 'get', '1/channels', [], 200], // getFromService
            ['owner', 'get', '2/channels', [], 401], // cannot see for not owned service
            ['owner', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], 200], // store
            ['owner', 'post', '2/channels', ['label' => 'test', 'service_id' => 2], 401], // cannot store on not owned service
            ['owner', 'get', '1/channels/1', [], 405], // show
            ['owner', 'put', '1/channels/1', ['label' => 'updated label', 'channel_id' => 1], 200], // update (full)
            ['owner', 'put', '1/channels/1', ['label' => 'updated label'], 200], // update (partial)
            ['owner', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => null], 200], // update (full)
            ['owner', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => 1], 200], // update (full)
            ['owner', 'put', '1/channels/7', ['label' => 'updated label', 'channel_id' => 7], 401],  // can't update channels from not membered service
            ['owner', 'patch', '1/channels/1', [], 405], // update (partial)
            ['owner', 'delete', '1/channels/1', [], 200], // destroy
            ['owner', 'delete', '2/channels/6', [], 401], // cannot destroy on not owned service
            // member user
            ['member', 'get', '1/channels', [], 200], // getFromService
            ['member', 'get', '2/channels', [], 401], // cannot see for not owned service
            ['member', 'post', '1/channels', ['label' => 'test', 'service_id' => 1], 200], // store
            ['member', 'post', '2/channels', ['label' => 'test', 'service_id' => 2], 401], // cannot store on not membered service
            ['member', 'get', '1/channels/1', [], 405], // show
            ['member', 'put', '1/channels/1', ['label' => 'updated label', 'channel_id' => 1], 200], // update (full)
            ['member', 'put', '1/channels/1', ['label' => 'updated label'], 200], // update (partial)
            ['member', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => null], 200], // update (full)
            ['member', 'put', '1/channels/1', ['label' => 'updated label', 'type_id' => 1], 200], // update (full)
            ['member', 'put', '1/channels/7', ['label' => 'updated label', 'channel_id' => 7], 401],  // can't update from not membered service
            ['member', 'patch', '1/channels/1', [], 405], // update (partial)
            ['member', 'delete', '1/channels/1', [], 200], // destroy
            ['member', 'delete', '2/channels/6', [], 401], // cannot destroy on not membered service
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
