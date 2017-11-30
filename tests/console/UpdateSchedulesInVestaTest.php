<?php

namespace Tests\Console;

use App\Jobs\UpdateVestaOpeninghours;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateSchedulesInVestaTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function commandFiresJobs()
    {
        $this->expectsJobs(UpdateVestaOpeninghours::class);
        factory(\App\Models\Service::class)->create(['source' => 'vesta']);
        \Artisan::call('openinghours:update-vesta');
    }
}
