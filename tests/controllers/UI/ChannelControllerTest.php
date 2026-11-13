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
     * @test
     */
    public function testStoreCreatesTheChannelInTheServiceOfTheUrl()
    {
        $this->actingAs(\App\Models\User::where('name', 'memberuser')->first(), 'api');

        $this->doRequest('post', $this->apiUrl . '/1/channels', ['label' => 'smuggled', 'service_id' => 3]);
        $this->seeStatusCode(200);

        $this->assertEquals(1, \App\Models\Channel::where('label', 'smuggled')->firstOrFail()->service_id);
        $this->assertFalse(\App\Models\Channel::where('label', 'smuggled')->where('service_id', 3)->exists());
    }

    /**
     * @test
     */
    public function testUpdateCanNotMoveAChannelToAnotherService()
    {
        $this->actingAs(\App\Models\User::where('name', 'memberuser')->first(), 'api');

        $this->doRequest('put', $this->apiUrl . '/1/channels/1', ['label' => 'moved?', 'service_id' => 3]);
        $this->seeStatusCode(200);

        $channel = \App\Models\Channel::find(1);
        $this->assertEquals('moved?', $channel->label);
        $this->assertEquals(1, $channel->service_id);
    }

    /**
     * @test
     */
    public function testStoreAndUpdateSetTheWeight()
    {
        $this->actingAs(\App\Models\User::where('name', 'memberuser')->first(), 'api');

        $this->doRequest('post', $this->apiUrl . '/1/channels', ['label' => 'weighted', 'weight' => 5]);
        $this->seeStatusCode(200);
        $channel = \App\Models\Channel::where('label', 'weighted')->firstOrFail();
        $this->assertEquals(5, $channel->weight);

        $this->doRequest('put', $this->apiUrl . '/1/channels/' . $channel->id, ['label' => 'weighted', 'weight' => -2]);
        $this->seeStatusCode(200);
        $this->assertEquals(-2, $channel->fresh()->weight);

        $this->doRequest('put', $this->apiUrl . '/1/channels/' . $channel->id, ['label' => 'weighted', 'weight' => '']);
        $this->seeStatusCode(200);
        $this->assertEquals(0, $channel->fresh()->weight);
    }

    /**
     * @test
     */
    public function testOrderSetsTheWeightOfEveryChannel()
    {
        $this->actingAs(\App\Models\User::where('name', 'memberuser')->first(), 'api');

        $ids = \App\Models\Channel::where('service_id', 1)->pluck('id')->reverse()->values()->all();
        $this->doRequest('put', $this->apiUrl . '/1/channels/order', ['channels' => $ids]);
        $this->seeStatusCode(200);

        $this->assertEquals($ids, array_column($this->response->json(), 'id'));
        foreach ($ids as $weight => $id) {
            $this->assertEquals($weight, \App\Models\Channel::find($id)->weight);
        }
        $this->assertEquals($ids, \App\Models\Service::find(1)->channels->pluck('id')->all());
    }

    /**
     * @test
     */
    public function testOrderMustContainExactlyTheChannelsOfTheService()
    {
        $this->actingAs(\App\Models\User::where('name', 'adminuser')->first(), 'api');

        $ids = \App\Models\Channel::where('service_id', 1)->pluck('id')->all();
        $otherId = \App\Models\Channel::where('service_id', '!=', 1)->value('id');

        // Missing a channel.
        $this->doRequest('put', $this->apiUrl . '/1/channels/order', ['channels' => array_slice($ids, 1)]);
        $this->seeStatusCode(422);

        // A channel of another service.
        $this->doRequest('put', $this->apiUrl . '/1/channels/order', ['channels' => array_merge($ids, [$otherId])]);
        $this->seeStatusCode(422);
        $this->assertEquals(0, \App\Models\Channel::find($otherId)->weight);
    }

    /**
     * @test
     */
    public function testInvalidWeightIsRejected()
    {
        $this->actingAs(\App\Models\User::where('name', 'memberuser')->first(), 'api');

        $this->doRequest('put', $this->apiUrl . '/1/channels/1', ['label' => 'invalid weight', 'weight' => 'abc']);
        // The exception handler renders validation errors as a 400.
        $this->seeStatusCode(400);
        $this->assertNotEquals('invalid weight', \App\Models\Channel::find(1)->label);
    }

    /**
     * @test
     */
    public function testUpdateFailsWhenTheChannelIsNotOfTheService()
    {
        $this->actingAs(\App\Models\User::where('name', 'adminuser')->first(), 'api');

        $this->doRequest('put', $this->apiUrl . '/1/channels/7', ['label' => 'updated label']);
        $this->seeStatusCode(422);
        $this->assertNotEquals('updated label', \App\Models\Channel::find(7)->label);
    }

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userRole', verb', 'uri', 'data', 'responce status'] // Resource controller action
     *
     * @return array
     */
    public static function requestTypeProvider()
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
            ['member', 'put', '2/channels/order', ['channels' => [6]], 401], // cannot order on not membered service
            // an id in the query string or body must not override the one in the URL
            ['member', 'get', '2/channels?service=1', [], 401],
            ['member', 'put', '1/channels/7', ['label' => 'updated label', 'channel_id' => 1], 401],
            ['member', 'put', '3/channels/7', ['label' => 'updated label', 'channel' => 1], 401],
            ['member', 'delete', '2/channels/6?channel_id=1', [], 401],
            ['member', 'delete', '2/channels/6', ['service' => 1], 401],
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
