<?php

namespace App\Monolog\Processor;

use Monolog\LogRecord;

/**
 * Processor that adds a base_url to the extra key of a log record.
 */
class BaseUrlProcessor
{

    /**
     * Adds the base_url to the record's extra key.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(LogRecord $record)
    {
        $record['extra']['base_url'] = '';
        if ($request = request()) {
            $record['extra']['base_url'] = $request->getSchemeAndHttpHost();
        }
        return $record;
    }
}
