<?php

namespace Tests\Controllers\UI;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServicesControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/ui/services';
    /**
     * @test
     */
    public function testGetNoUiServicesWithoutAuth()
    {
        //$this->withoutMiddleware();

        $call = $this->doRequest('GET', $this->apiUrl);
        $call->seeStatusCode(401);
    }

    /**
     * @test
     */
    public function testGetUiServicesAsAdminRole()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');
        $call = $this->doRequest('GET', $this->apiUrl);
        $content = $this->getContentStructureTested($call);
        $this->assertCount(count(\App\Models\Service::all()), $content);
    }

    /**
     * @test
     */
    public function testGetUiServicesAsMemberRole()
    {
        $user = \App\Models\User::find(2);
        $this->actingAs($user, 'api');

        $call = $this->doRequest('GET', $this->apiUrl);
        $content = $this->getContentStructureTested($call);
        $this->assertCount(1, $content);
    }

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userRole', verb', 'uri', 'data', 'responce status'] // Resource controller action
     * @return array
     */
    public function requestTypeProvider()
    {
        return [
            //  unauth user
            ['unauth', 'get', '', [], '401'], // index
            ['unauth', 'post', '', [], '405'], // store
            ['unauth', 'get', '1', [], '401'], // show
            ['unauth', 'put', '1', [], '401'], // update (full)
            ['unauth', 'patch', '1', ['draft' => false], '401'], // update (partial)
            ['unauth', 'delete', '1', [], '405'], // destroy
            // admin user
            ['admin', 'get', '', [], '200'], // index
            ['admin', 'post', '', [], '405'], // store
            ['admin', 'get', '1', [], '200'], // show
            ['admin', 'put', '1', [], '200'], // update (full)
            ['admin', 'patch', '1', ['draft' => false], '200'], // update (partial)
            ['admin', 'delete', '1', [], '405'], // destroy
            // owner user
            ['owner', 'get', '', [], '200'], // index
            ['owner', 'post', '', [], '405'], // store
            ['owner', 'get', '1', [], '200'], // show
            ['owner', 'put', '1', [], '200'], // update (full)
            ['owner', 'patch', '1', ['draft' => false], '200'], // update (partial)
            ['owner', 'delete', '1', [], '405'], // destroy
            // member user
            ['member', 'get', '', [], '200'], // index
            ['member', 'post', '', [], '405'], // store
            ['member', 'get', '1', [], '200'], // show
            ['member', 'put', '1', [], '401'], // update (full)
            ['member', 'patch', '1', ['draft' => false], '401'], // update (partial)
            ['member', 'delete', '1', [], '405'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testUIServiceRequests($userRole, $verb, $pathArg, $data, $statusCode)
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
