<?php


namespace App\Services;


use App\Models\QueuedJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\AssignOp\Mod;

class QueueService
{

    /**
     * Singleton class instance.
     *
     * @var SparqlService
     */
    private static $instance;

    /**
     * GetInstance for Singleton pattern
     *
     * @return SparqlService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new QueueService();
        }

        return self::$instance;
    }

    /**
     * @param ShouldQueue $job
     * @param Model $object
     */
    public function addJobToQueue(ShouldQueue $job, $className, $externalId)
    {
        $criteria = [
            'class' => $className,
            'job' => get_class($job),
            'external_id' => $externalId,
        ];

        $queuedJob = QueuedJob::where($criteria)->first();

        if (!$queuedJob) {
            $queuedJob = new QueuedJob();
            $queuedJob->class = $className;
            $queuedJob->job = get_class($job);
            $queuedJob->external_id = $externalId;
            $queuedJob->save();

            dispatch($job);
        }
    }

    /**
     * @param ShouldQueue $job
     * @param Model $model
     */
    public function removeJobFromQueue(ShouldQueue $job, $className, $externalId)
    {
        $criteria = [
            'class' => $className,
            'job' => get_class($job),
            'external_id' => $externalId,
        ];

        $queuedJob = QueuedJob::where($criteria)->first();

        if ($queuedJob) {
            $queuedJob->delete();
        }
    }

}