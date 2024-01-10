<?php

namespace App\Monolog\Processor;

use Monolog\LogRecord;

/**
 * Processor that transforms the level name to lowercase in a log record.
 */
class LowerCaseLevelNameProcessor
{

    /**
     * Transforms the level name to lowercase in the given record.
     *
     * @param \Monolog\LogRecord $record
     *
     * @return \Monolog\LogRecord
     */
    public function __invoke(LogRecord $record)
    {
        $record['extra']['level_name'] = strtolower($record['level_name']);

        return $record;
    }
}
