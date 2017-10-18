<?php

namespace Tests\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServicesUiControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function testGetNoUiServicesWithoutAuth()
    {
        //$this->withoutMiddleware();
        $call = $this->json('get', '/api/ui/services');
        $call->seeStatusCode(401);
    }

    /**
     * @test
     */
    public function testGetUiServicesAsAdminRole()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');
        $call = $this->json('get', '/api/ui/services');
        $call->seeStatusCode(200);

        $result = $call->decodeResponseJson();
        $this->assertCount(count(\App\Models\Service::all()), $result);
    }

    /**
     * @test
     */
    public function testGetUiServicesAsMemberRole()
    {
        $user = \App\Models\User::find(2);
        $this->actingAs($user, 'api');
        $call = $this->json('get', '/api/ui/services');
        $call->seeStatusCode(200);

        $result = $call->decodeResponseJson();
        $this->assertCount(1, $result);
    }

    /**
     * Data provider for requests
     *
     * Datastructure:
     * ['userId', verb', 'uri', 'data', 'responce status'] // Resource controller action
     * @return array
     */
    public function requestTypeProvider()
    {
        return [
            //  unauth user
            ['', 'get', '/api/ui/services', [], '401'], // index
            ['', 'get', '/api/ui/services/create', [], '401'], // create
            ['', 'post', '/api/ui/services', [], '401'], // store
            ['', 'get', '/api/ui/services/1', [], '401'], // show
            ['', 'get', '/api/ui/services/1/edit', [], '401'], // edit
            ['', 'put', '/api/ui/services/1', [], '401'], // update (full)
            ['', 'patch', '/api/ui/services/1', ['draft' => false], '401'], // update (partial)
            ['', 'delete', '/api/ui/services/1', [], '401'], // destroy
            // admin user
            ['1', 'get', '/api/ui/services', [], '200'], // index
            ['1', 'get', '/api/ui/services/create', [], '501'], // create
            ['1', 'post', '/api/ui/services', [], '501'], // store
            ['1', 'get', '/api/ui/services/1', [], '200'], // show
            ['1', 'get', '/api/ui/services/1/edit', [], '501'], // edit
            ['1', 'put', '/api/ui/services/1', [], '200'], // update (full)
            ['1', 'patch', '/api/ui/services/1', ['draft' => false], '200'], // update (partial)
            ['1', 'delete', '/api/ui/services/1', [], '501'], // destroy
            // regular user
            ['2', 'get', '/api/ui/services', [], '200'], // index
            ['2', 'get', '/api/ui/services/create', [], '501'], // create
            ['2', 'post', '/api/ui/services', [], '501'], // store
            ['2', 'get', '/api/ui/services/1', [], '200'], // show
            ['2', 'get', '/api/ui/services/1/edit', [], '501'], // edit
            ['2', 'put', '/api/ui/services/1', [], '405'], // update (full)
            ['2', 'patch', '/api/ui/services/1', ['draft' => false], '405'], // update (partial)
            ['2', 'delete', '/api/ui/services/1', [], '501'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testRequestsByUserWithRoleAndCheckStatusCode($userId, $verb, $path, $data, $statusCode)
    {
        if ($userId) {
            $authUser = \App\Models\User::find($userId);
            $this->actingAs($authUser, 'api');
        }
        $call = $this->json(
            $verb,
            $path,
            $data,
            [
                'Accept' => 'application/json;',
                'Accept-Encoding' => 'gzip, deflate',
                'Accept-Language' => 'nl-NL,nl;q=0.8,en-US;q=0.6,en;q=0.4',
                'X-Requested-With' => 'XMLHttpRequest',
            ]
        );
        $call->seeStatusCode($statusCode);
    }
}
