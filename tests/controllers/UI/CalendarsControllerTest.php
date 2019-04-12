<?php

namespace Tests\Controllers\UI;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalendarsControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui/calendars';
    /**
     * @var string
     */
    protected $versionUrl = '/api/v1/ui/openinghours';

    /**
     * @test
     */
    public function testFailOnDestroyWithWrongId()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');

        $this->doRequest('delete', $this->apiUrl . '/' . '958688468468468');
        $this->seeStatusCode(422);
        $content = $this->decodeResponseJson();
        
        $this->assertEquals([
            'error' => [
                'code' => 'ModelNotFoundException',
                'message' => 'Calendar model is not found with given identifier',
                'target' => 'Calendar',
            ],
        ], $content);
    }

    /**
     * @test
     */
    public function testPriorityOfCalendarUpdatesWhenASiblingIsRemoved()
    {
        $authUser = \App\Models\User::where('name', 'adminuser')->first();
        $this->actingAs($authUser, 'api');

        $this->doRequest('get', $this->versionUrl . '/' . '1');
        $this->seeStatusCode(200);

        $cal = $this->decodeResponseJson()['calendars'][2];
        $this->assertEquals(3, $cal['id']);
        $this->assertEquals(-2, $cal['priority']);

        $this->doRequest('delete', $this->apiUrl . '/' . '2');
        $this->seeStatusCode(200);

        $this->doRequest('get', $this->versionUrl . '/' . '1');
        $this->seeStatusCode(200);

        $cal = $this->decodeResponseJson()['calendars'][1];
        $this->assertEquals(3, $cal['id']);
        $this->assertNotEquals(-2, $cal['priority']);
        $this->assertEquals(-1, $cal['priority']);
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
            ['unauth', 'delete', '2', [], '401'], // destroy

            // admin user
            ['admin', 'get', '', [], '405'], // index
            ['admin', 'post', '', $data, '200'], // store
            ['admin', 'get', '1', [], '405'], // show
            ['admin', 'put', '1', $data, '200'], // update (full)
            ['admin', 'patch', '1', $data, '200'], // update (partial)
            ['admin', 'delete', '1', [], '400'], // can't delete base calendar
            ['admin', 'delete', '2', [], '200'], // destroy

            // editor user
            ['editor', 'get', '', [], '405'], // index
            ['editor', 'post', '', $data, '200'], // store
            ['editor', 'get', '1', [], '405'], // show
            ['editor', 'put', '1', $data, '200'], // update (full)
            ['editor', 'patch', '1', $data, '200'], // update (partial)
            ['editor', 'delete', '1', [], '400'], // can't delete base calendar
            ['editor', 'delete', '2', [], '200'], // destroy

            // owner user
            ['owner', 'get', '', [], '405'], // index
            ['owner', 'post', '', $data, '200'], // store
            ['owner', 'get', '1', [], '405'], // show
            ['owner', 'put', '1', $data, '200'], // update (full)
            ['owner', 'patch', '1', $data, '200'], // update (partial)
            ['owner', 'delete', '1', [], '400'], // can't delete base calendar
            ['owner', 'delete', '2', [], '200'], // destroy
            ['owner', 'delete', '22', [], '401'], // can't delete calendar from not Owned service

            // member user
            ['member', 'get', '', [], '405'], // index
            ['member', 'post', '', $data, '200'], // store
            ['member', 'get', '1', [], '405'], // show
            ['member', 'put', '1', $data, '200'], // update (full)
            ['member', 'patch', '1', $data, '200'], // update (partial)
            ['member', 'delete', '1', [], '400'], // can't delete base calendar
            ['member', 'delete', '2', [], '200'], // destroy
            ['member', 'delete', '22', [], '401'], // can't delete calendar from not membered service
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

    /**
     * @test
     */
    public function testPostWithBulkInserEvents()
    {
        $event1 = (array) factory(Event::class)->make();
        $event2 = (array) factory(Event::class)->make();
        $data = [
            'priority' => '2',
            'label' => 'test',
            'openinghours_id' => '1',
            'events' => [$event1, $event2],
        ];

        $this->requestsByUserWithRoleAndCheckStatusCode('admin', 'post', '', $data, '200');
    }

    /**
     * @test
     */
    public function testPutWithBulkInserEvents()
    {
        $event1 = (array) factory(Event::class)->make();
        $event2 = (array) factory(Event::class)->make();
        $data = [
            'priority' => '2',
            'label' => 'test',
            'openinghours_id' => '1',
            'events' => [$event1, $event2],
        ];

        $this->requestsByUserWithRoleAndCheckStatusCode('admin', 'put', '2', $data, '200');
    }
}
