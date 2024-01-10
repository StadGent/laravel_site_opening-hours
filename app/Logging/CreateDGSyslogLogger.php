<?php

namespace App\Logging;

use App\Monolog\Processor\BaseUrlProcessor;
use App\Monolog\Processor\ClientIpProcessor;
use App\Monolog\Processor\LowerCaseLevelNameProcessor;
use App\Monolog\Processor\TimestampProcessor;
use App\Monolog\Processor\UidProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;

class CreateDGSyslogLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger(env('APP_NAME'));

        // Client ip processor.
        $logger->pushProcessor(new ClientIpProcessor());

        // Base URL processor.
        $logger->pushProcessor(new BaseUrlProcessor());

        // Lowercase level name processor.
        $logger->pushProcessor(new LowerCaseLevelNameProcessor());

        // Timestamp processor.
        $logger->pushProcessor(new TimestampProcessor());

        // UID processor.
        $logger->pushProcessor(new UidProcessor());

        // Syslog handler.
        $handler = new SyslogHandler(
            'openingsuren',
            defined('LOG_LOCAL4') ? LOG_LOCAL4 : 160,
            'debug',
            true,
            LOG_ODELAY
        );

        // Format parseable for kibana.
        $formatter = new LineFormatter(
            '%extra.base_url%'
            . '|%extra.timestamp%'
            . '|laravel'
            . '|%extra.level_name%'
            . '|%extra.client_ip%'
            . '|%extra.base_url%%extra.url%'
            . '|%extra.referrer%'
            . '|%extra.uid%'
            . '||%message%'
        );
        $handler->setFormatter($formatter);

        // Set the handler.
        $logger->setHandlers([$handler]);

        return $logger;
    }
}
