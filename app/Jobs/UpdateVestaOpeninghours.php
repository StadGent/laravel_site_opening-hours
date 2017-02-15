<?php

namespace App\Jobs;

use App\Formatters\FormatsOpeninghours;
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
    public function __construct(string $vestaUid, int $serviceId)
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
        (new VestaService())->updateOpeninghours($this->vestaUid, $this->formatWeek($this->serviceId));
    }
}
