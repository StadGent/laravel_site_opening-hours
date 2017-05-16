<?php

namespace App\Jobs;

use App\Formatters\FormatsOpeninghours;
use App\Services\VestaService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateVestaOpeninghours implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, FormatsOpeninghours;

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
    public function handle()
    {
        // Call the VestaService to write the output away
        $output = '';

        try {
            $output = $this->formatWeek($this->serviceId, 'html', '', \Carbon\Carbon::today()->startOfWeek());
        } catch (\Exception $ex) {
            \Log::warning('No output was created for VESTA for service with UID ' . $this->vestaUid);
        }

        (new VestaService())->updateOpeninghours($this->vestaUid, $output);
    }
}
