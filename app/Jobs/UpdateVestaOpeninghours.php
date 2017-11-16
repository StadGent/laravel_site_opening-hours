<?php

namespace App\Jobs;

use App\Formatters\Openinghours\HtmlFormatter;
use App\Models\Service;
use App\Services\OpeninghoursService;
use App\Services\VestaService;
use Carbon\Carbon;
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
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($vestaUid, $serviceId)
    {
        $this->vestaUid = $vestaUid;
        $this->serviceId = $serviceId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(OpeninghoursService $openinghoursService, VestaService $vestaService, HtmlFormatter $formatter)
    {
        // Call the VestaService to write the output away
        $output = '';

        try {
            $start = (new Carbon())->startOfWeek();
            $end = (new Carbon())->endOfWeek();
            $openinghoursService->collectData($start, $end, Service::find($this->serviceId));
            $output = $formatter
              ->render($openinghoursService->getData())
              ->getOutput();
        } catch (\Exception $ex) {
            \Log::warning('No output was created for VESTA for service with UID ' . $this->vestaUid);
        }

        $result = $vestaService->updateOpeninghours($this->vestaUid, $output);
        if (!$result) {
            $this->fail(new \Exception(sprintf(
                'The %s job failed with vesta uid %s and service id %s. Check the logs for details',
                static::class,
                $this->vestaUid,
                $this->serviceId
            )));
        }
    }
}
