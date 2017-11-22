<?php

namespace App\Jobs;

use App\Repositories\LodOpeninghoursRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteLodOpeninghours implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $serviceId;

    /**
     * @var int
     */
    protected $openinghoursId;

    /**
     * Create a new job instance.
     *
     * @param int $serviceId
     * @param int $openinghoursId
     *
     * @return void
     */
    public function __construct($serviceId, $openinghoursId)
    {
        $this->serviceId = $serviceId;

        $this->openinghoursId = $openinghoursId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result = app(LodOpeninghoursRepository::class)->deleteOpeninghours($this->openinghoursId);
        if (!$result) {
            $this->fail(new \Exception(sprintf('The %s job failed with service id %s and opening hours id %s. Check the logs for details', static::class, $this->serviceId, $this->openinghoursId)));
        }
    }
}
