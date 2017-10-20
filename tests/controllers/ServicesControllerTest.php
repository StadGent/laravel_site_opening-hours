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
            ['', 'post', '/api/services', [], '405'], // store
            ['', 'get', '/api/services/1', [], '200'], // show
            ['', 'put', '/api/services/1', [], '405'], // update (full)
            ['', 'patch', '/api/services/1', ['draft' => false], '405'], // update (partial)
            ['', 'delete', '/api/services/1', [], '405'], // destroy
            // admin user
            ['1', 'get', '/api/services', [], '200'], // index
            ['1', 'post', '/api/services', [], '405'], // store
            ['1', 'get', '/api/services/1', [], '200'], // show
            ['1', 'put', '/api/services/1', [], '405'], // update (full)
            ['1', 'patch', '/api/services/1', ['draft' => false], '405'], // update (partial)
            ['1', 'delete', '/api/services/1', [], '405'], // destroy
            // regular user
            ['2', 'get', '/api/services', [], '200'], // index
            ['2', 'post', '/api/services', [], '405'], // store
            ['2', 'get', '/api/services/1', [], '200'], // show
            ['2', 'put', '/api/services/1', [], '405'], // update (full)
            ['2', 'patch', '/api/services/1', ['draft' => false], '405'], // update (partial)
            ['2', 'delete', '/api/services/1', [], '405'], // destroy
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
                'Accept-type' => 'application/json',
            ]
        );
        $call->seeStatusCode($statusCode);
    }
}
