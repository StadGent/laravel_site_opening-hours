<?php

namespace Tests\Jobs;

use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Service;
use App\Services\RecurringOHService;
use App\Services\VestaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateVestaOpeninghoursTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    private $initQDriver;

    /**
     * setup for each test
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->initQDriver = env('QUEUE_CONNECTION');
        config(['queue.default' => 'sync']);

        $this->app->singleton(RecurringOHService::class, function () {
            $mock = $this->createMock(\App\Services\RecurringOHService::class, ['getRecurringOHForService']);
            $mock->expects($this->atLeastOnce())
                ->method('getServiceOutput')
                ->willReturn(date('ymdhis'));

            return $mock;
        });
    }

    public function tearDown(): void
    {
        config(['queue.default' => $this->initQDriver]);
        parent::tearDown();
    }

    /**
     * @test
     * @group validation
     */
    public function testFailOnWrongService()
    {
        $service = factory(Service::class)->create(['identifier' => 'JyeehBaby', 'source' => 'recreatex']);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The App\Jobs\UpdateVestaOpeninghours job failed for App\Models\Service (' . $service->id .
            '). Check the logs for details. - Incompatible with VESTA or uid JyeehBab'
        );
        $job = new UpdateVestaOpeninghours($service->identifier, $service->id, true);

        $queue = dispatch($job);
    }

    /**
     * @test
     * @group validation
     */
    public function testRemoveOnEmptyService()
    {
        $this->app->singleton(RecurringOHService::class, function () {
            $mock = $this->createMock(\App\Services\RecurringOHService::class, ['getServiceOutput']);
            $mock->expects($this->atLeastOnce())
                ->method('getServiceOutput')
                ->willReturn('');

            return $mock;
        });
        $this->app->singleton(VestaService::class, function () {
            $mock = $this->createMock(\App\Services\VestaService::class, ['updateOpeninghours']);
            $mock->expects($this->atLeastOnce())
                ->method('emptyOpeninghours')
                ->willReturn(true);

            return $mock;
        });

        $service = factory(Service::class)->create(['identifier' => 'JyeehBaby', 'source' => 'vesta']);
        $job = new UpdateVestaOpeninghours($service->identifier, $service->id, true);
        $job->handle();
        $this->assertTrue(true);
    }

    /**
     * @test
     * @group validation
     */
    public function testFailForDraft()
    {
        $service = factory(Service::class)->create(['identifier' => 'JyeehBaby', 'source' => 'vesta', 'draft' => 1]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'The App\Jobs\UpdateVestaOpeninghours job failed for App\Models\Service (' . $service->id .
            '). Check the logs for details. - Service is inactive'
        );
        $job = new UpdateVestaOpeninghours($service->identifier, $service->id, true);

        $queue = dispatch($job);
    }

    /**
     * no error = good
     * @test
     * @group validation
     */
    public function testHappyPath()
    {
        $this->app->singleton(VestaService::class, function () {
            $mock = $this->createMock(\App\Services\VestaService::class, ['updateOpeninghours']);
            $mock->expects($this->atLeastOnce())
                ->method('updateOpeninghours')
                ->willReturn(true);

            return $mock;
        });

        $service = Service::find(1);
        $service->source = 'vesta';
        $service->identifier = 'JyeehBaby';
        $service->save();

        $job = new UpdateVestaOpeninghours($service->identifier, $service->id);
        $job->handle();
        $this->assertTrue(true);
    }
}
