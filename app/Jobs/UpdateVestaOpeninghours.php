<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\RecurringOHService;
use App\Services\VestaService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * This JOB will collect the Openinghours data for the next 3 months of a service
 * and send this to VESTA
 *
 * Job will be triggered by the OBSERVERS on models to update by alteration on MODEL
 * OR by the COMMAND UpdateSchedulesInVesta to keep texts for next 3 months up to date
 *
 * Currently all output is in Dutch nl-BE
 */
class UpdateVestaOpeninghours extends BaseJob implements ShouldQueue
{
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
        parent::__construct();
        $this->vestaUid = $vestaUid;
        $this->serviceId = $serviceId;
        $this->test = $test;

        $this->extModelClass = Service::class;
        $this->extId = $serviceId;
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
            $this->letsFail('Incompatible with VESTA or uid ' . $this->vestaUid);
        }
        $service = $serviceCollection->first();
        if ($service->draft) {
            $this->letsFail('Service is inactive');
        }

        try {
            $recurringOHService = app(RecurringOHService::class);
            $output = $recurringOHService->getRecurringOHForService($service);
            if ($output === '') {
                throw new \Exception('No data was found to send to VESTA.', 1);
            }
            $this->sendToVesta($service, $output);
        } catch (\Exception $ex) {
            $this->letsFail($ex->getMessage());
        }
    }

    /**
     * @param string $output
     */
    protected function sendToVesta($service, $output)
    {
        $vService = app(VestaService::class);
        $vService->setClient();
        $synced = $vService->updateOpeninghours($service->identifier, $output);
        if (!$synced) {
            $this->letsFail('Not able to send the data to VESTA.');
        }
        \Log::info('New data for (' . $service->id . ') ' . $service->label . ' VESTA UID ' .
                $service->identifier . ' is send to VESTA.');
        \Log::info('Service (' . $service->id . ') ' . $service->label . ' with UID ' .
            $service->identifier . ' is sync with VESTA.');

        $this->letsFinish();
    }
}
