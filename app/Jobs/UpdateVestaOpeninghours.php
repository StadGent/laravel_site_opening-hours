<?php

namespace App\Jobs;

use App\Formatters\Openinghours\HtmlFormatter;
use App\Formatters\Openinghours\TextFormatter;
use App\Models\Service;
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
     * The formatter to use for Vesta.
     * @var TextFormatter
     */

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($vestaUid, $serviceId)
    {
        $this->vestaUid = $vestaUid;
        $this->serviceId = $serviceId;
        $this->formatter = new HtmlFormatter();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Call the VestaService to write the output away
        $output = '';

        try {
            $openinghoursService = app('OpeninghoursService');
            $start = (new Carbon())->startOfWeek();
            $end = (new Carbon())->endOfWeek();
            $openinghoursService->collectData($start, $end, Service::find($this->serviceId));
            $output = $this->formatter->render($openinghoursService->getData());
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
    }
}
