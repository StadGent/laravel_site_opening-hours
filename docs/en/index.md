# Opening hours

*[Nederlandstalige versie](../nl/index.md)*

The opening hours platform (the "Openingsuren Invoermodule") centralises the
management of the opening hours of the services of the City of Ghent and their
channels (counter, phone, by appointment, ...).

Staff manage the opening hours in a web interface. The platform then makes them
available:

- through a **public REST API** (JSON, JSON-LD, HTML and plain text), used by
  stad.gent, Visit Gent and RADAR among others;
- as **Linked Open Data** (SPARQL / Virtuoso);
- in **VESTA**, the CRM holding the city's business partners, so the legacy
  integrations (e.g. InfoFiches) stay up to date.

## Overview

| | |
| --- | --- |
| Status | Production |
| Environments | [Production](https://openingsuren.gent.be/), [QA](https://openingsuren.qa.gent.be/), [Test](https://openingsuren.dv.gent.be/) |
| Technology | Laravel 12, PHP 8.3, MySQL/MariaDB, Vue 2 (bundled with Rollup) |
| Authentication | Local users (Laravel auth), API tokens through Laravel Passport |
| Queue | Laravel queue with **exactly one** worker (supervisor) |
| Logging | Syslog (ident `openingsuren`, facility `LOCAL4`) in Kibana format |
| Integrations | VESTA (SOAP), SPARQL/LOD (Virtuoso), RecreateX (SOAP), holiday iCal calendars on stad.gent |
| Source code | [StadGent/laravel_site_opening-hours](https://github.com/StadGent/laravel_site_opening-hours) |
| Owner | d09-web-developers |

## Contents

### Functional

- [Services and channels](functional/services-and-channels.md)
- [Managing opening hours](functional/opening-hours.md)
- [Users and roles](functional/users-and-roles.md)
- [Public API](functional/api.md)

### Technical

- [Architecture](technical/architecture.md)
- [Integrations](technical/integrations.md)
- [Operations and deployment](technical/operations.md)
- [Local development](technical/local-development.md)
- [Debugging](technical/debugging.md)
