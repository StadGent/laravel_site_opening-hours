<?php

namespace Tests\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServicesControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/services';

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
            ['unauth', 'get', '', [], '200'], // index
            ['unauth', 'post', '', [], '405'], // store
            ['unauth', 'get', '1', [], '200'], // show
            ['unauth', 'put', '1', [], '405'], // update (full)
            ['unauth', 'patch', '1', ['draft' => false], '405'], // update (partial)
            ['unauth', 'delete', '1', [], '405'], // destroy
            // admin user
            ['admin', 'get', '', [], '200'], // index
            ['admin', 'post', '', [], '405'], // store
            ['admin', 'get', '1', [], '200'], // show
            ['admin', 'put', '1', [], '405'], // update (full)
            ['admin', 'patch', '1', ['draft' => false], '405'], // update (partial)
            ['admin', 'delete', '1', [], '405'], // destroy
            // owner user
            ['owner', 'get', '', [], '200'], // index
            ['owner', 'post', '', [], '405'], // store
            ['owner', 'get', '1', [], '200'], // show
            ['owner', 'put', '1', [], '405'], // update (full)
            ['owner', 'patch', '1', ['draft' => false], '405'], // update (partial)
            ['owner', 'delete', '1', [], '405'], // destroy
            // member user
            ['member', 'get', '', [], '200'], // index
            ['member', 'post', '', [], '405'], // store
            ['member', 'get', '1', [], '200'], // show
            ['member', 'put', '1', [], '405'], // update (full)
            ['member', 'patch', '1', ['draft' => false], '405'], // update (partial)
            ['member', 'delete', '1', [], '405'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testServiceRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
