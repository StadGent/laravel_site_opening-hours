<?php

namespace App\Monolog\Processor;

/**
 * Processor that adds a timestamp to a log record.
 */
class TimestampProcessor
{

    /**
     * Adds the timestamp to the record.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['timestamp'] = time();
        if (isset($record['datetime'])
            && $record['datetime'] instanceof \DateTime
        ) {
            $record['timestamp'] = $record['datetime']->getTimestamp();
        }

        return $record;
    }
}
