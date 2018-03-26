<?php

namespace App\Monolog\Processor;

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
    public function __invoke(array $record)
    {
        $record['extra']['base_url'] = '';
        if ($request = request()) {
            $record['extra']['base_url'] = $request->getSchemeAndHttpHost();
        }
        return $record;
    }
}
