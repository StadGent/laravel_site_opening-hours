# Architectuur

```text
                 ┌──────────────────────────────┐
 Beheerders ───▶ │  Vue 2 SPA (beheerinterface) │
                 └──────────────┬───────────────┘
                                │ /api/v1/ui/* (Passport, cookie)
                 ┌──────────────▼───────────────┐      ┌───────────────┐
 Websites,  ───▶ │   Laravel 12 applicatie      │◀────▶│ MySQL/MariaDB │
 RADAR, ...      │   /api/v1/* (publiek)        │      └───────────────┘
                 └──────┬───────────────┬───────┘
                        │ observers     │ artisan commands
                 ┌──────▼──────┐        │
                 │ Queue (1x)  │        │
                 └──┬───────┬──┘        │
          SOAP      │       │ SPARQL    │ SOAP / SPARQL / iCal
        ┌───────────▼┐  ┌───▼───────────▼──┐  ┌───────────┐  ┌────────────────┐
        │   VESTA    │  │ Virtuoso (LOD)   │  │ RecreateX │  │ iCal stad.gent │
        └────────────┘  └──────────────────┘  └───────────┘  └────────────────┘
```

## Lagen

| Map | Verantwoordelijkheid |
| --- | --- |
| `app/Http/Controllers` | Publieke API (`QueryController`, `ServicesController`, ...) en de UI-API (`UI\*`) |
| `app/Http/Middleware` | Rolcontroles (`IsAdmin`, `IsOwner`, `HasRoleInService`) |
| `app/Http/Transformers` | Omzetting naar JSON, JSON-LD, HTML en tekst |
| `app/Models` | Eloquent-modellen; `Ical` en `DayInfo` berekenen de openingsuren |
| `app/Observers` | Zetten sync-jobs op de queue bij wijzigingen |
| `app/Jobs` | Sync naar VESTA en LOD, ophalen van diensten |
| `app/Services` | Koppelingen (`VestaService`, `SparqlService`), tekstuele weergave (`RecurringOHService`), `QueueService` |
| `app/Repositories` | Data-toegang, o.a. `LodServicesRepository` en `LodOpeninghoursRepository` |
| `app/Policies/UserPolicy.php` | Regels voor gebruikersbeheer |
| `app/Console/Commands` | Artisan commands, zie [Beheer](beheer.md) |
| `resources/assets/js` | Vue-frontend |
| `robo/` | Deploy-hook voor de Digipolis Robo-deploy |

## Openingsuren berekenen

`app/Models/Ical.php` bouwt per versie een VCALENDAR met de events van alle
gepubliceerde kalenders. De `PRIORITY` van elk event is `priority + 20` van de
kalender; sluitingskalenders krijgen `STATUS:CLOSED` en beslaan de hele dag.
Met `johngrogg/ics-parser` en `rlanvin/php-rrule` wordt per dag bepaald welke
events gelden; enkel de events met de laagste priority-waarde (de bovenste laag)
tellen. Het resultaat is een lijst `DayInfo`-objecten (`date`, `open`,
`hours`).

## Asynchrone verwerking

Wijzigingen worden niet synchroon naar externe systemen geschreven:

| Observer | Trigger | Jobs |
| --- | --- | --- |
| `ServiceObserver` | Dienst opgeslagen (VESTA-bron) | `UpdateVestaOpeninghours` of `DeleteVestaOpeninghours` (draft) |
| `OpeninghoursObserver` | Versie opgeslagen of verwijderd | `UpdateVestaOpeninghours` (actieve versie, VESTA-bron), `UpdateLodOpeninghours` / `DeleteLodOpeninghours` |
| `CalendarObserver` | Kalender opgeslagen | `UpdateVestaOpeninghours` (actieve versie, VESTA-bron), `UpdateLodOpeninghours` |
| `ChannelObserver` | Kanaal verwijderd | `UpdateVestaOpeninghours` (VESTA-bron), `DeleteLodChannel` |

`QueueService` en de tabel `queued_jobs` voorkomen dat dezelfde job dubbel in de
queue staat.

!!! warning "Exact één queue worker"
    LOD-updates bestaan uit een DELETE gevolgd door een INSERT. Met meerdere
    workers kunnen die door elkaar lopen en krijg je dirty reads/writes op het
    SPARQL-endpoint. Draai daarom exact **één** worker (supervisor) en gebruik
    nooit de `sync`-driver in productie.

## Beveiliging

- **Authenticatie**: Laravel-sessie voor de webinterface; de SPA roept
  `/api/v1/ui/*` aan met de `auth:api`-guard (Laravel Passport, via de
  `CreateFreshApiToken`-cookie).
- **CSP**: `spatie/laravel-csp` met de preset
  `app/Csp/Presets/OpeningHoursPreset.php` (nonces voor scripts, styles en
  fonts). Instelbaar met `CSP_ENABLED` en `CSP_REPORT_URI`.
- **Publieke API**: rate limit `throttle:240,1`.

## Logging

`app/Logging/CreateDGSyslogLogger.php` logt naar syslog (ident `openingsuren`,
facility `LOCAL4`) in het Digipolis Kibana-formaat. De processors in
`app/Monolog/Processor` voegen client-IP, base-URL, timestamp en user-ID toe.
Jobs loggen `JOB SUCCES` of `JOB FAIL`.
