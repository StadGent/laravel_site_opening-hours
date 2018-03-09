<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->configureMonologUsing(function (\Monolog\Logger $monolog) {
    // Client ip processor.
    $monolog->pushProcessor(function (array $record) {
        $record['extra']['client_ip'] = 'unavailable';
        $request = request();
        if ($request) {
            $record['extra']['client_ip'] = $request->getClientIp();
        }
        return $record;
    });

    // Base URL processor.
    $monolog->pushProcessor(function (array $record) {
        $record['extra']['base_url'] = '';
        if ($request = request()) {
            $record['extra']['base_url'] = $request->getSchemeAndHttpHost();
        }
        return $record;
    });

    // Lowercase level name processor.
    $monolog->pushProcessor(function (array $record) {
        $record['level_name'] = strtolower($record['level_name']);
        return $record;
    });

    // Timestamp processor.
    $monolog->pushProcessor(function (array $record) {
        $record['timestamp'] = time();
        if (isset($record['datetime']) && $record['datetime'] instanceof \DateTime) {
            $record['timestamp'] = $record['datetime']->getTimestamp();
        }
        return $record;
    });

    // UID processor.
    $monolog->pushProcessor(function (array $record) {
        $record['extra']['uid'] = 0;
        $user = auth()->user();
        $uid = $user ? $user->getAuthIdentifier() : null;
        if (null === $uid) {
            return $record;
        }

        $record['extra']['uid'] = $uid;
        return $record;
    });

    // Syslog handler.
    $handler = new Monolog\Handler\SyslogHandler('openingsuren', defined('LOG_LOCAL4') ? LOG_LOCAL4 : 160, 'debug', true, LOG_ODELAY);

    // Format parseable for kibana.
    $formatter = new Monolog\Formatter\LineFormatter('%extra.base_url%|%timestamp%|laravel|%level_name%|%extra.client_ip%|%extra.base_url%%extra.url%|%extra.referrer%|%extra.uid%||%message%');
    $handler->setFormatter($formatter);

    // Set the handler.
    $monolog->setHandlers([$handler]);
});

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
