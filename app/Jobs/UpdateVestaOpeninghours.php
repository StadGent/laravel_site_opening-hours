<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\RecurringOHService;
use App\Services\VestaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * This JOB will collect the Openinghours data for the next 3 months of a service
 * and send this to VESTA
 *
 * Job will be triggered by the OBSERVERS on models to update by alteration on MODEL
 * OR by the COMMAND UpdateSchedulesInVesta to keep texts for next 3 months up to date
 *
 * Currently all output is in Dutch nl-BE
 */
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
     * @var boolean
     */
    protected $test;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($vestaUid, $serviceId, $test = false)
    {
        $this->vestaUid = $vestaUid;
        $this->serviceId = $serviceId;
        $this->test = $test;
    }

    /**
     * Execute the job.
     *
     * Sync the data for the next 3 months to VESTA
     * Checks:
     * - the service must be active (draft = 1 will fail)
     * - there is actualy be data to send to VESTA (empty output will fail)
     * - the data to be send is different from what is already in VESTA (idential will not be send)
     *
     * @return void
     */
    public function handle()
    {
        $serviceCollection = Service::where('id', $this->serviceId)
            ->where('source', 'vesta')
            ->where('identifier', $this->vestaUid);
        if ($serviceCollection->count() != 1) {
            $this->letsFail('The %s job did not find a VESTA service (%s) with uid %s.');
        }
        $service = $serviceCollection->first();
        if ($service->draft) {
            $this->letsFail('The %s job tried to sync an inactive service (%s) with uid %s to VESTA.');
        }

        try {
            $recurringOHService = app(RecurringOHService::class);
            $output = $recurringOHService->getRecurringOHForService($service);
            if ($output === '') {
                $this->letsFail('The %s job tried to sync empty data for service (%s) with uid %s to VESTA.');
            }
            $this->sendToVesta($service, $output);
        } catch (\Exception $ex) {
            $this->fail($ex);
        }
    }

    /**
     * lest make a method for this repeating code
     * @param $message
     */
    protected function letsFail($message)
    {
        $errorMsg = sprintf($message, static::class, $this->serviceId, $this->vestaUid);
        $this->fail(new \Exception($errorMsg));
    }

    /**
     * @param string $output
     */
    protected function sendToVesta($service, $output)
    {
        $vService = app(VestaService::class);
        $vService->setClient();
        $currentContent = $vService->getOpeningshoursByGuid($service->identifier);
        if ($currentContent != $output) {
            $vService->updateOpeninghours($service->identifier, $output);
            \Log::info('New data for (' . $service->id . ') ' . $service->label . ' VESTA UID ' .
                $service->identifier . ' is send to VESTA.');
        }
        \Log::info('Service (' . $service->id . ') ' . $service->label . ' with UID ' .
            $service->identifier . ' is sync with VESTA.');
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        \Log::error('FAIL: ' . $exception->getMessage());
        if ($this->test) {
            throw $exception;
        }
    }
}
