<?php

namespace Tests\Console;

use App\Jobs\FetchServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

class FetchServicesTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function commandFiresJobs()
    {
        Bus::fake();
        \Artisan::call('openinghours:fetch-services');
        Bus::assertDispatched(FetchServices::class);
    }
}
