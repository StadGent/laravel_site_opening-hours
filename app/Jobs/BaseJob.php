<?php

namespace App\Jobs;

use App\Services\QueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $extModelClass;

    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * @var integer
     */
    protected $extId;

    public function __construct()
    {
        $this->queueService = app(QueueService::class);
    }

    /**
     * Finish with success
     *
     * Set info message in log
     * And removeJobFromQueue
     * @param $class
     * @param $externId²
     */
    protected function letsFinish()
    {
        \Log::info('JOB SUCCES: ' . static::class . ': ' . $this->extModelClass . ' - ' . $this->extId);
        $this->queueService->removeJobFromQueue($this, $this->extModelClass, $this->extId);
    }

    /**
     * lest make a method for this repeating code
     * @param $message
     */
    protected function letsFail($errorMsg = '')
    {
        $jobMsg = sprintf('The %s job failed', static::class);
        if (!empty($this->extModelClass) && !empty($this->extId)) {
            $jobStr = ' for %s (%s). Check the logs for details.';
            $jobMsg .= sprintf($jobStr, $this->extModelClass, $this->extId);
        }
        if (!empty($errorMsg)) {
            $jobMsg .= ' - ' . $errorMsg;
        }

        $this->fail(new \Exception($jobMsg));
    }

    /**
     * The job failed to process.
     * Set error message in log
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('JOB FAIL: ' . $exception->getMessage());
        if (!empty($this->extModelClass) && !empty($this->extId)) {
            $this->queueService->removeJobFromQueue($this, $this->extModelClass, $this->extId);
        }
        if (isset($this->test) && $this->test) {
            throw $exception;
        }
    }
}
