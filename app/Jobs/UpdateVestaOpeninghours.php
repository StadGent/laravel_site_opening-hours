<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateVestaOpeninghours implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The UID of the service in VESTA
     * @var string
     */
    private $vestaUid;

    /**
     * The ID of the service
     * @var int
     */
    private $serviceId;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($vestaUid, $serviceId)
    {
        $this->vestaUid = $vestaUid;
        $this->serviceId = $serviceId;
        $this->queueService = app('QueueService');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {
            // TODO : generate html output for full week
            $output = '';
        } catch (\Exception $ex) {
            \Log::warning('No output was created for VESTA for service with UID ' . $this->vestaUid);
        }

        $result = app('VestaService')->updateOpeninghours($this->vestaUid, $output);
        if (!$result) {
            $this->fail(new \Exception(sprintf(
                'The %s job failed with vesta uid %s and service id %s. Check the logs for details',
                static::class,
                $this->vestaUid,
                $this->serviceId
            )));
        }

        $this->queueService->removeJobFromQueue($this, Service::class, $this->serviceId);

    }
}
