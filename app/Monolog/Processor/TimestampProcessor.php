<?php

namespace App\Monolog\Processor;

use Monolog\LogRecord;

/**
 * Processor that adds a timestamp to a log record.
 */
class TimestampProcessor
{

    /**
     * Adds the timestamp to the record.
     *
     * @param \Monolog\LogRecord $record
     *
     * @return \Monolog\LogRecord
     */
    public function __invoke(LogRecord $record)
    {
        $record['extra']['timestamp'] = time();
        if (isset($record['datetime'])
            && $record['datetime'] instanceof \DateTime
        ) {
            $record['extra']['timestamp'] = $record['datetime']->getTimestamp();
        }

        return $record;
    }
}
