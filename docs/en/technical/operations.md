# Operations and deployment

## Artisan commands

| Command | Purpose |
| --- | --- |
| `openinghours:fetch-services` | Fetch services from LOD (through the queue) |
| `openinghours:fetch-recreatex` | Import the opening hours of sports venues from RecreateX |
| `openinghours:fetch-od-holidays` | Load the holiday and school holiday calendars |
| `openinghours:update-vesta` | Queue a VESTA update for all active VESTA services |
| `openinghours:print-vesta-output {service-id}` | Show what would be sent to VESTA |
| `openinghours:print-lod-input {type}` | Show how many services the LOD query returns |
| `openinghours:print-ical-yearly-closing-period {name} {from} {to}` | Generate VEVENTs for a yearly recurring closing period |

## Scheduled tasks

The Laravel scheduler (`app/Console/Kernel.php`) only runs
`cache:prune-stale-tags` (hourly). Add the standard cron for it:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

!!! warning
    `openinghours:fetch-services` and `openinghours:update-vesta` are commented
    out in the scheduler. They must run through a separate cron on the server
    or manually. The same goes for `fetch-recreatex` and `fetch-od-holidays`.

## Queue worker

Run **exactly one** worker under supervisor (see
[Architecture](architecture.md#asynchronous-processing)):

```ini
[program:openingsuren-worker]
command=php /path/to/app/artisan queue:work --tries=3
numprocs=1
autostart=true
autorestart=true
```

Use a real queue driver in production (`database`, `redis` or `beanstalkd`),
never `sync`.

## Deployment

Deployments use the Digipolis Robo tooling
(`digipolisgent/robo-digipolis-laravel`). The hook in
`robo/Robo/EventHandler/OpeninghoursClearCacheHandler.php` runs
`php artisan queue:restart` after the deploy, so the worker loads the new code.

Done manually, an update comes down to:

```bash
composer install --no-dev
npm ci && npm run build && npm run sass
php artisan migrate --force
php artisan cache:clear
php artisan queue:restart
```

## Configuration

See `.env.example` for all variables. The main groups:

| Variables | Purpose |
| --- | --- |
| `DB_*` | Database |
| `QUEUE_CONNECTION`, `SESSION_DRIVER`, `CACHE_DRIVER` | Drivers (no `file` sessions in production) |
| `MAIL_*` | SMTP for invites and password resets |
| `BASE_URI`, `DATA_REPRESENTATION_URI` | Base URIs for the LOD triples (no trailing slash) |
| `SPARQL_WRITE_*` | SPARQL endpoint, credentials and graph |
| `VESTA_ENDPOINT`, `VESTA_KEY`, `VESTA_SOURCE_URL` | VESTA integration |
| `RECREATEX_URI`, `SHOP_ID`, `CHANNEL_NAME`, `CALENDAR_NAME`, `SPORTS_UUIDS`, `SPORTS_RESERVATION_CHANNEL_WEIGHT` | RecreateX import |
| `SUPER_ADMIN_ID` | ID of the super admin |
| `CSP_ENABLED`, `CSP_REPORT_URI` | Content Security Policy |

## CI

`.github/workflows/tests.yml` runs PHPUnit on every pull request and on pushes
to `develop` and `master` (PHP 8.3 with `soap` and `intl`, MySQL 8).
