<?php

namespace App\Jobs;

use App\Models\Openinghours;
use App\Repositories\LodOpeninghoursRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteLodOpeninghours extends BaseJob implements ShouldQueue
{

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
        parent::__construct();
        $this->serviceId = $serviceId;
        $this->openinghoursId = $openinghoursId;

        $this->extModelClass = Openinghours::class;
        $this->extId = $openinghoursId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!app(LodOpeninghoursRepository::class)->deleteOpeninghours($this->openinghoursId)) {
            $this->letsFail();
        }
        $this->letsFinish();
    }
}
