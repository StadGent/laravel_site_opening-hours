<?php

namespace Tests\Console;

use App\Jobs\UpdateVestaOpeninghours;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;

class UpdateSchedulesInVestaTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function commandFiresJobs()
    {
        Bus::fake();
        \App\Models\Service::factory()->create(['source' => 'vesta']);
        \Artisan::call('openinghours:update-vesta');
        Bus::assertDispatched(UpdateVestaOpeninghours::class);
    }
}
