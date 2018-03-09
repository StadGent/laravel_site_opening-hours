<?php

namespace App\Monolog\Processor;

/**
 * Processor that adds a client_ip to the extra key of a log record.
 */
class ClientIpProcessor
{

    /**
     * @var string
     */
    protected $cachedClientIp = null;

    /**
     * Adds the client_ip to the record's extra key.
     *
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        // Yhe client_ip will hold the request's actual origin address.
        $record['extra']['client_ip'] = $this->cachedClientIp
            ? $this->cachedClientIp
            : 'unavailable';

        // Return if we already know client's IP
        if ($record['extra']['client_ip'] !== 'unavailable') {
            return $record;
        }

        $request = request();
        if ($request) {
            $this->cachedClientIp = $request->getClientIp();
        }

        $record['extra']['client_ip'] = $this->cachedClientIp;

        return $record;
    }
}
