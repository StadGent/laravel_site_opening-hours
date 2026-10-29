# Beheer en deployment

## Artisan commands

| Command | Doel |
| --- | --- |
| `openinghours:fetch-services` | Diensten ophalen uit LOD (via de queue) |
| `openinghours:fetch-recreatex` | Openingsuren van sportlocaties importeren uit RecreateX |
| `openinghours:fetch-od-holidays` | Feestdagen- en vakantiekalenders inladen |
| `openinghours:update-vesta` | Voor alle actieve VESTA-diensten een VESTA-update op de queue zetten |
| `openinghours:print-vesta-output {service-id}` | Tonen wat naar VESTA gestuurd zou worden |
| `openinghours:print-lod-input {type}` | Tonen hoeveel diensten de LOD-query teruggeeft |
| `openinghours:print-ical-yearly-closing-period {name} {from} {to}` | VEVENTs genereren voor een jaarlijks terugkerende sluitingsperiode |

## Geplande taken

De Laravel-scheduler (`app/Console/Kernel.php`) draait enkel
`cache:prune-stale-tags` (elk uur). Voeg daarvoor de standaard cron toe:

```cron
* * * * * cd /pad/naar/app && php artisan schedule:run >> /dev/null 2>&1
```

!!! warning
    `openinghours:fetch-services` en `openinghours:update-vesta` staan in de
    scheduler in commentaar. Ze moeten via een aparte cron op de server of
    manueel uitgevoerd worden. Hetzelfde geldt voor `fetch-recreatex` en
    `fetch-od-holidays`.

## Queue worker

Draai exact **één** worker onder supervisor (zie
[Architectuur](architectuur.md#asynchrone-verwerking)):

```ini
[program:openingsuren-worker]
command=php /pad/naar/app/artisan queue:work --tries=3
numprocs=1
autostart=true
autorestart=true
```

Gebruik in productie een echte queue-driver (`database`, `redis` of
`beanstalkd`), nooit `sync`.

## Deployment

De deployment gebeurt met de Digipolis Robo-tooling
(`digipolisgent/robo-digipolis-laravel`). De hook in
`robo/Robo/EventHandler/OpeninghoursClearCacheHandler.php` voert na de deploy
`php artisan queue:restart` uit, zodat de worker de nieuwe code laadt.

Manueel komt een update neer op:

```bash
composer install --no-dev
npm ci && npm run build && npm run sass
php artisan migrate --force
php artisan cache:clear
php artisan queue:restart
```

## Configuratie

Zie `.env.example` voor alle variabelen. De belangrijkste groepen:

| Variabelen | Doel |
| --- | --- |
| `DB_*` | Database |
| `QUEUE_CONNECTION`, `SESSION_DRIVER`, `CACHE_DRIVER` | Drivers (sessies niet op `file` in productie) |
| `MAIL_*` | SMTP voor uitnodigingen en wachtwoordresets |
| `BASE_URI`, `DATA_REPRESENTATION_URI` | Basis-URI's voor de LOD-triples (zonder trailing slash) |
| `SPARQL_WRITE_*` | SPARQL-endpoint, credentials en graph |
| `VESTA_ENDPOINT`, `VESTA_KEY`, `VESTA_SOURCE_URL` | VESTA-koppeling |
| `RECREATEX_URI`, `SHOP_ID`, `CHANNEL_NAME`, `CALENDAR_NAME`, `SPORTS_UUIDS`, `SPORTS_RESERVATION_CHANNEL_WEIGHT` | RecreateX-import |
| `SUPER_ADMIN_ID` | ID van de super admin |
| `CSP_ENABLED`, `CSP_REPORT_URI` | Content Security Policy |

## CI

`.github/workflows/tests.yml` draait PHPUnit op elke pull request en op pushes
naar `develop` en `master` (PHP 8.3 met `soap` en `intl`, MySQL 8).
