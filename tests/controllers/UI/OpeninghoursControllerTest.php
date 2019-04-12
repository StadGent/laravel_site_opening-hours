<?php

namespace Tests\Controllers\UI;

use App\Models\Openinghours;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OpeninghoursControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui/openinghours';

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
            'label' => 'test',
            'channel_id' => '1',
            'start_date' => '2019-01-01',
            'end_date' => '2019-02-01',
        ];

        return [
            //  unauth user
            ['unauth', 'get', '', [], '405'], // index
            ['unauth', 'post', '', [], '401'], // store
            ['unauth', 'get', '1', [], '401'], // show
            ['unauth', 'put', '1', [], '401'], // update (full)
            ['unauth', 'patch', '1', [], '401'], // update (partial)
            ['unauth', 'delete', '1', [], '401'], // destroy
            ['unauth', 'delete', '1', [], '401'], // destroy
            // admin user
            ['admin', 'get', '', [], '405'], // index
            ['admin', 'post', '', $data, '200'], // store
            ['admin', 'post', '', [
                'label' => 'test',
                'channel_id' => '1',
                'start_date' => '2017-09-01',
                'end_date' => '2018-02-01',
            ], '400'], // post overlap
            ['admin', 'get', '1', [], '200'], // show
            ['admin', 'put', '1', $data, '200'], // update (full)
            ['admin', 'put', '2', $data, '400'], // error parent child mismatch
            ['admin', 'patch', '1', $data, '200'], // update (partial)
            ['admin', 'delete', '1', [], '200'], // destroy
            ['admin', 'delete', '6854635468', [], '422'], // destroy no model match
            // editor user
            ['editor', 'get', '', [], '405'], // index
            ['editor', 'post', '', $data, '200'], // store
            ['editor', 'get', '1', [], '200'], // show
            ['editor', 'put', '1', $data, '200'], // update (full)
            ['editor', 'patch', '1', $data, '200'], // update (partial)
            ['editor', 'delete', '1', [], '200'], // destroy

            // owner user
            ['owner', 'get', '', [], '405'], // index
            ['owner', 'post', '', $data, '200'], // store
            ['owner', 'get', '1', [], '200'], // show
            ['owner', 'get', '7', [], '401'], // can't get from not owned service
            ['owner', 'put', '1', $data, '200'], // update (full)
            ['owner', 'put', '7', $data, '401'], // can't update from not owned service
            ['owner', 'patch', '1', $data, '200'], // update (partial)
            ['owner', 'patch', '7', $data, '401'], // can't update from not owned service
            ['owner', 'delete', '1', [], '200'], // destroy
            ['owner', 'delete', '7', [], '401'], // can't delete from not owned service
            // member user
            ['member', 'get', '', [], '405'], // index
            ['member', 'post', '', $data, '200'], // store
            ['member', 'get', '1', [], '200'], // show
            ['member', 'get', '7', [], '401'], // can't get from not membered service
            ['member', 'put', '1', $data, '200'], // update (full)
            ['member', 'put', '7', $data, '401'], // can't update from not membered service
            ['member', 'patch', '1', $data, '200'], // update (partial)
            ['member', 'patch', '7', $data, '401'], // can't update from not membered service
            ['member', 'delete', '1', [], '200'], // destroy
            ['member', 'delete', '7', [], '401'], // can't delete from not membered service
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUIOpeninghoursRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
    public function testUpdateOverlapGivesError()
    {
        $this->requestsByUserWithRoleAndCheckStatusCode(
            'admin',
            'post',
            '',
            [
                'label' => 'test',
                'channel_id' => '1',
                'start_date' => '2019-09-01',
                'end_date' => '2019-02-01',
            ],
            '200'
        );

        $lastOh = Openinghours::latest()->first();

        $call = $this->doRequest('PUT', $this->apiUrl . '/' . $lastOh->id, [
            'label' => 'test',
            'channel_id' => '1',
            'start_date' => '2017-09-01',
            'end_date' => '2019-02-01',
        ]);
        $call->seeStatusCode(400);
        $call->seeJsonEquals([
            'message' => 'Er is een overlapping met een andere versie.',
        ]);
    }
}
