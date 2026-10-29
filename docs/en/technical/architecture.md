# Architecture

```text
                 ┌──────────────────────────────┐
 Editors    ───▶ │  Vue 2 SPA (management UI)   │
                 └──────────────┬───────────────┘
                                │ /api/v1/ui/* (Passport, cookie)
                 ┌──────────────▼───────────────┐      ┌───────────────┐
 Websites,  ───▶ │   Laravel 12 application     │◀────▶│ MySQL/MariaDB │
 RADAR, ...      │   /api/v1/* (public)         │      └───────────────┘
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

## Layers

| Directory | Responsibility |
| --- | --- |
| `app/Http/Controllers` | Public API (`QueryController`, `ServicesController`, ...) and the UI API (`UI\*`) |
| `app/Http/Middleware` | Role checks (`IsAdmin`, `IsOwner`, `HasRoleInService`) |
| `app/Http/Transformers` | Conversion to JSON, JSON-LD, HTML and text |
| `app/Models` | Eloquent models; `Ical` and `DayInfo` compute the opening hours |
| `app/Observers` | Queue sync jobs on changes |
| `app/Jobs` | Sync to VESTA and LOD, fetching services |
| `app/Services` | Integrations (`VestaService`, `SparqlService`), textual output (`RecurringOHService`), `QueueService` |
| `app/Repositories` | Data access, e.g. `LodServicesRepository` and `LodOpeninghoursRepository` |
| `app/Policies/UserPolicy.php` | User management rules |
| `app/Console/Commands` | Artisan commands, see [Operations](operations.md) |
| `resources/assets/js` | Vue frontend |
| `robo/` | Deploy hook for the Digipolis Robo deploy |

## Computing opening hours

`app/Models/Ical.php` builds a VCALENDAR per version with the events of all
published calendars. Each event's `PRIORITY` is the calendar's `priority + 20`;
closing calendars get `STATUS:CLOSED` and cover the whole day. Using
`johngrogg/ics-parser` and `rlanvin/php-rrule` it determines per day which
events apply; only the events with the lowest priority value (the top layer)
count. The result is a list of `DayInfo` objects (`date`, `open`, `hours`).

## Asynchronous processing

Changes are not written to external systems synchronously:

| Observer | Trigger | Jobs |
| --- | --- | --- |
| `ServiceObserver` | Service saved (VESTA source) | `UpdateVestaOpeninghours` or `DeleteVestaOpeninghours` (draft) |
| `OpeninghoursObserver` | Version saved or deleted | `UpdateVestaOpeninghours` (active version, VESTA source), `UpdateLodOpeninghours` / `DeleteLodOpeninghours` |
| `CalendarObserver` | Calendar saved | `UpdateVestaOpeninghours` (active version, VESTA source), `UpdateLodOpeninghours` |
| `ChannelObserver` | Channel deleted | `UpdateVestaOpeninghours` (VESTA source), `DeleteLodChannel` |

`QueueService` and the `queued_jobs` table prevent the same job from being
queued twice.

!!! warning "Exactly one queue worker"
    LOD updates consist of a DELETE followed by an INSERT. With multiple
    workers these can interleave, causing dirty reads/writes on the SPARQL
    endpoint. Run **exactly one** worker (supervisor) and never use the `sync`
    driver in production.

## Security

- **Authentication**: Laravel session for the web interface; the SPA calls
  `/api/v1/ui/*` with the `auth:api` guard (Laravel Passport, through the
  `CreateFreshApiToken` cookie).
- **CSP**: `spatie/laravel-csp` with the preset
  `app/Csp/Presets/OpeningHoursPreset.php` (nonces for scripts, styles and
  fonts). Configurable with `CSP_ENABLED` and `CSP_REPORT_URI`.
- **Public API**: rate limit `throttle:240,1`.

## Logging

`app/Logging/CreateDGSyslogLogger.php` logs to syslog (ident `openingsuren`,
facility `LOCAL4`) in the Digipolis Kibana format. The processors in
`app/Monolog/Processor` add the client IP, base URL, timestamp and user ID.
Jobs log `JOB SUCCES` or `JOB FAIL`.
