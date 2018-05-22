<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\VestaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * This JOB will delete openinghours in vesta for inactive services.
 *
 * Job will be triggered by the OBSERVERS on models to update by alteration on
 * MODEL OR by the COMMAND DeleteSchedulesInVesta to keep texts for next 3
 * months up to date
 *
 * Currently all output is in Dutch nl-BE
 */
class DeleteVestaOpeninghours extends BaseJob implements ShouldQueue
{

    /**
     * The UID of the service in VESTA
     *
     * @var string
     */
    private $vestaUid;

    /**
     * The ID of the service
     *
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
     * Send request to VESTA to delete openinghours for inactive services.
     * Checks:
     * - the service must not be active (draft = 0 will fail)
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

        if ( ! $service->draft) {
            $this->letsFail('Service was not draft.');
        }

        try {
            $this->sendToVesta($service);
        } catch (\Exception $ex) {
            $this->letsFail($ex->getMessage());
        }
    }

    /**
     * @param string $output
     */
    protected function sendToVesta($service)
    {
        $vService = app(VestaService::class);
        $vService->setClient();
        $synced = $vService->emptyOpeninghours($service->identifier);
        if ( ! $synced) {
            $this->letsFail('Not able to send the data to VESTA.');
        }
        Log::info('Request to empty openinghours for (' . $service->id . ') ' . $service->label . ' VESTA UID ' .
            $service->identifier . ' is send to VESTA.');
        Log::info('Service (' . $service->id . ') ' . $service->label . ' with UID ' .
            $service->identifier . ' is sync with VESTA.');

        $this->letsFinish();
    }
}
