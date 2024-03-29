<?php

namespace Tests\Controllers\UI;

use App\Models\Channel;
use App\Models\Openinghours;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServicesControllerTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected $apiUrl = '/api/v1/ui/services';

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
            ['unauth', 'get', '', [], 401], // index
            ['unauth', 'post', '', [], 405], // store
            ['unauth', 'get', '1', [], 405], // show
            ['unauth', 'put', '1', [], 401], // update (full)
            ['unauth', 'patch', '1', ['draft' => false], 401], // update (partial)
            ['unauth', 'delete', '1', [], 405], // destroy
            // admin user
            ['admin', 'get', '', [], 200], // index
            ['admin', 'post', '', [], 405], // store
            ['admin', 'get', '1', [], 405], // show
            ['admin', 'put', '1', [], 200], // update (full)
            ['admin', 'patch', '1', ['draft' => false], 200], // update (partial)
            ['admin', 'delete', '1', [], 405], // destroy
            // editor user
            ['editor', 'get', '', [], 200], // index
            ['editor', 'post', '', [], 405], // store
            ['editor', 'get', '1', [], 405], // show
            ['editor', 'put', '1', [], 401], // update (full)
            ['editor', 'patch', '1', ['draft' => false], 401], // update (partial)
            ['editor', 'delete', '1', [], 405], // destroy
            // owner user
            ['owner', 'get', '', [], 200], // index
            ['owner', 'post', '', [], 405], // store
            ['owner', 'get', '1', [], 405], // show
            ['owner', 'put', '1', [], 401], // update (full)
            ['owner', 'patch', '1', ['draft' => false], 401], // update (partial)
            ['owner', 'delete', '1', [], 405], // destroy
            // member user
            ['member', 'get', '', [], 200], // index
            ['member', 'post', '', [], 405], // store
            ['member', 'get', '1', [], 405], // show
            ['member', 'put', '1', [], 401], // update (full)
            ['member', 'patch', '1', ['draft' => false], 401], // update (partial)
            ['member', 'delete', '1', [], 405], // destroy
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

    /**
     * @test
     */
    public function testChildInfoIndicatorsOnServicesOutput()
    {
        // make some fake data to limit the collected data
        $service = Service::factory()->create();
        $user = User::factory()->create();
        app('UserRepository')->linkToService($user->id, $service->id, 'Owner');
        $this->actingAs($user, 'api');

        // check countChannels output for No channels info
        $this->doRequest('get', $this->apiUrl, []);
        $requestOutput = $this->getContentStructureTested();
        $this->assertEquals(0, $requestOutput[0]['countChannels']);

        // add channel check has_missing_oh for Missing calendar info
        $channel = Channel::factory()->create(['service_id' => $service->id]);
        $this->doRequest('get', $this->apiUrl, []);
        $requestOutput = $this->getContentStructureTested();
        $this->assertEquals(1, $requestOutput[0]['has_missing_oh']);
        $this->assertEquals(1, $requestOutput[0]['has_inactive_oh']);

        $now = new Carbon();
        // add OH check has_inactive_oh for Missing active calendar info
        $openinghours = Openinghours::factory()->create([
            'channel_id' => $channel->id,
            'active' => 0,
            'start_date' => $now->copy()->addYear(),
            'end_date' =>$now->copy()->addYear(),
        ]);
        $this->doRequest('get', $this->apiUrl, []);
        $requestOutput = $this->getContentStructureTested();
        $this->assertEquals(0, $requestOutput[0]['has_missing_oh']);
        $this->assertEquals(1, $requestOutput[0]['has_inactive_oh']);

        // make OH active check no info indicators
        $openinghours->active = 1;
        $openinghours->start_date = $now->copy()->subYear();
        $openinghours->save();

        $this->doRequest('get', $this->apiUrl, []);
        $requestOutput = $this->getContentStructureTested();
        $this->assertEquals(0, $requestOutput[0]['has_missing_oh']);
        $this->assertEquals(0, $requestOutput[0]['has_inactive_oh']);
    }
}
