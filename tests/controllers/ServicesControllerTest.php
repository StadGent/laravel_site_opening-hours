<?php

namespace Tests\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServicesControllerTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function testGetServicesWithoutAuth()
    {
        $call = $this->json('get', '/api/services');
        $call->seeStatusCode(200);

        $result = $call->decodeResponseJson();
        $this->assertCount(count(\App\Models\Service::all()), $result);
    }

    /**
     * @test
     */
    public function testGetServicesAsAdminRole()
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user, 'api');
        $call = $this->json('get', '/api/services');
        $call->seeStatusCode(200);

        $result = $call->decodeResponseJson();
        $this->assertCount(count(\App\Models\Service::all()), $result);
    }

    /**
     * @test
     */
    public function testGetServicesAsMemberRole()
    {
        $user = \App\Models\User::find(2);
        $this->actingAs($user, 'api');
        $call = $this->json('get', '/api/services');
        $call->seeStatusCode(200);

        $result = $call->decodeResponseJson();
        $this->assertCount(count(\App\Models\Service::all()), $result);
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
            ['', 'get', '/api/services', [], '200'], // index
            ['', 'get', '/api/services/create', [], '501'], // create
            ['', 'post', '/api/services', [], '501'], // store
            ['', 'get', '/api/services/1', [], '200'], // show
            ['', 'get', '/api/services/1/edit', [], '501'], // edit
            ['', 'put', '/api/services/1', [], '501'], // update (full)
            ['', 'patch', '/api/services/1', ['draft' => false], '501'], // update (partial)
            ['', 'delete', '/api/services/1', [], '501'], // destroy
            // admin user
            ['1', 'get', '/api/services', [], '200'], // index
            ['1', 'get', '/api/services/create', [], '501'], // create
            ['1', 'post', '/api/services', [], '501'], // store
            ['1', 'get', '/api/services/1', [], '200'], // show
            ['1', 'get', '/api/services/1/edit', [], '501'], // edit
            ['1', 'put', '/api/services/1', [], '501'], // update (full)
            ['1', 'patch', '/api/services/1', ['draft' => false], '501'], // update (partial)
            ['1', 'delete', '/api/services/1', [], '501'], // destroy
            // regular user
            ['2', 'get', '/api/services', [], '200'], // index
            ['2', 'get', '/api/services/create', [], '501'], // create
            ['2', 'post', '/api/services', [], '501'], // store
            ['2', 'get', '/api/services/1', [], '200'], // show
            ['2', 'get', '/api/services/1/edit', [], '501'], // edit
            ['2', 'put', '/api/services/1', [], '501'], // update (full)
            ['2', 'patch', '/api/services/1', ['draft' => false], '501'], // update (partial)
            ['2', 'delete', '/api/services/1', [], '501'], // destroy
        ];
    }

    /**
     * @test
     * @dataProvider requestTypeProvider
     */
    public function testRequestsByUserWithRoleAndCheckStatusCode($userId, $verb, $path, $data, $statusCode)
    {
        if ($userId) {
            $adminUser = \App\Models\User::find($userId);
            $this->actingAs($adminUser, 'api');
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
