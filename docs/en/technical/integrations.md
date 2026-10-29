# Integrations

## Fetching services from LOD

`openinghours:fetch-services` queues the `FetchServices` job. It queries the
services through SPARQL (`app/Repositories/LodServicesRepository.php`) and
creates or updates them based on `source` + `identifier`.

| Source | Graph | Selection |
| --- | --- | --- |
| `vesta` | `http://stad.gent/agents/` | `foaf:Agent` with a `dct:source` starting with `VESTA` |
| `recreatex` | `http://stad.gent/agents/` | `foaf:Agent` with a `dct:source` starting with `RECREATEX` |
| `publicToilets` | `http://stad.gent/public-restrooms/` | `schema:PublicToilet` |
| `crm` | `http://stad.gent/agents-crm/` | `schema:Organization` with a `dct:source` starting with `CRM` |

A VESTA business partner only ends up in LOD (and therefore in the platform)
when "Publiceren in LOD" is checked. New services arrive as drafts.

!!! note
    **Reading** also happens through the SPARQL write endpoint
    (`SPARQL_WRITE_*`). The `SPARQL_ENDPOINT*` and
    `SPARQL_WRITE_INSERT_ENDPOINT` variables in `.env.example` are not used by
    the code.

## Opening hours to LOD

`UpdateLodOpeninghours` writes an RDF graph per version through SPARQL Update
(first a DELETE of the old triples, then an `INSERT DATA`) to the graph in
`SPARQL_WRITE_GRAPH`. `DeleteLodOpeninghours` and `DeleteLodChannel` remove
triples.

- Vocabularies: `cv:` (`http://data.europa.eu/m8g/`), `oh:`
  (`http://semweb.datasciencelab.be/ns/oh#`) and iCal (`Vcalendar`, `Vevent`).
- URIs are built from `BASE_URI` (`/service/{id}`, `/channel/{id}`,
  `/openinghours/{id}`, ...) in `app/helpers.php`. Channels point to
  `DATA_REPRESENTATION_URI/channel/{id}` with `rdfs:isDefinedBy`.

## VESTA

VESTA is the CRM holding the city's business partners. The platform writes a
textual (HTML) rendering of the opening hours to the `ves_openingsuren` field
of the business partner, so existing integrations reading from VESTA (e.g.
InfoFiches) keep working.

- SOAP web service at `VESTA_ENDPOINT` (`?wsdl`), API key `VESTA_KEY` as the
  `X-API-Key` SOAP header.
- Operations: `SearchJSON` (read the current value), `FillHours` (write),
  `EmptyHours` (clear).
- `UpdateVestaOpeninghours` sends the opening hours from today up to 2 months
  ahead (`config('app.vesta_output_months')`), formatted by
  `RecurringOHService`. Nothing is written when the value is unchanged.
- Only for services with source `vesta`; the `identifier` is the VESTA GUID.
- `VESTA_SOURCE_URL` is only used in the UI to link to the business partner in
  VESTA (placeholder `${identifier}`).

!!! warning
    Because VESTA receives a 2-month window, `openinghours:update-vesta` must
    run regularly (e.g. weekly), otherwise the text in VESTA goes stale even
    when nothing changes in the platform. See
    [Operations](operations.md#scheduled-tasks).

## RecreateX

`openinghours:fetch-recreatex` imports the opening hours of sports venues
(services with source `recreatex`) from the RecreateX web shop (SOAP at
`RECREATEX_URI`).

- `FindInfrastructureOpenings` with `SHOP_ID`, for the current year up to 3
  years ahead. The openings go into a channel `CHANNEL_NAME` and calendar
  `CALENDAR_NAME`.
- RecreateX stores every day separately; the command converts them into weekly
  RRULEs.
- For every UUID in `SPORTS_UUIDS` (comma-separated), `FindReservations` fetches
  the reservations per month and creates a channel per activity (type
  "Algemeen", weight `SPORTS_RESERVATION_CHANNEL_WEIGHT`).
- Channels that were no longer processed are cleared.

## Public holidays and school holidays

`openinghours:fetch-od-holidays` loads the iCal files from `config/app.php`
(`supported_exception_callendars`: public holidays, school holidays, Gentse
Feesten, extra closing days of the City of Ghent) into the `default_calendars`
and `default_events` tables. The UI offers them as presets
(`GET /api/v1/ui/presets`).
