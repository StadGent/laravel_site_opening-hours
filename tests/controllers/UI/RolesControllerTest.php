<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class RolesControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/ui/roles';

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userRole', verb', 'uri', 'data', 'responce status'] // Resource controller action
     * @return array
     */
    public function requestTypeProvider()
    {
        $data = [
            'user_id' => '2',
            'service_id' => '1',
            'role' => 'Member',
        ];
        $dataRemove = [
            'user_id' => '2',
            'service_id' => '1',
        ];

        return [
            //  unauth user
            ['unauth', 'get', '', [], '405'], // index
            ['unauth', 'post', '', $data, '405'], // store
            ['unauth', 'get', '1', [], '404'], // show
            ['unauth', 'put', '', $data, '405'], // update (full)
            ['unauth', 'patch', '', $data, '401'], // update (partial)
            ['unauth', 'delete', '', $dataRemove, '401'], // destroy
            // admin user
            ['admin', 'get', '', [], '405'], // index
            ['admin', 'post', '', $data, '405'], // store
            ['admin', 'get', '1', [], '404'], // show
            ['admin', 'put', '', $data, '405'], // update (full)
            ['admin', 'patch', '', $data, '200'], // update (partial)
            ['admin', 'delete', '', $dataRemove, '200'], // destroy
            // owner user
            ['owner', 'get', '', [], '405'], // index
            ['owner', 'post', '', $data, '405'], // store
            ['owner', 'get', '1', [], '404'], // show
            ['owner', 'put', '', $data, '405'], // update (full)
            ['owner', 'patch', '', $data, '200'], // update (partial)
            ['owner', 'delete', '', $dataRemove, '200'], // destroy
            // member user
            ['member', 'get', '', [], '405'], // index
            ['member', 'post', '', $data, '405'], // store
            ['member', 'get', '1', [], '404'], // show
            ['member', 'put', '', $data, '405'], // update (full)
            ['member', 'patch', '', $data, '401'], // update (partial)
            ['member', 'delete', '', $dataRemove, '401'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUIRoleRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
