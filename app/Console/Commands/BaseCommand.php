<?php

namespace App\Console\Commands;

use App\Services\QueueService;
use Illuminate\Console\Command;

class BaseCommand extends Command
{
    /**
     * @var QueueService
     */
    protected $queueService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->queueService = app(QueueService::class);
    }

    /**
     * Write a string as error output.
     *
     * overwrite parent to make sure errors go to log
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        \Log::error($string);
        parent::error($string, $verbosity);
    }

    /**
     * Write a string as info output.
     *
     * overwrite parent to make info go to log
     *
     * @param  string  $string
     * @param  null|int|string  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        \Log::info($string);
        parent::info($string, $verbosity);
    }
}
