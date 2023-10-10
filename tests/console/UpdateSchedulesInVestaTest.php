<?php

namespace Tests\Console;

use App\Jobs\UpdateVestaOpeninghours;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateSchedulesInVestaTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function commandFiresJobs()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        \App\Models\Service::factory()->create(['source' => 'vesta']);
        \Artisan::call('openinghours:update-vesta');
    }
}
