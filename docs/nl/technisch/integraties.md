# Integraties

## Diensten ophalen uit LOD

`openinghours:fetch-services` zet de job `FetchServices` op de queue. Die vraagt
via SPARQL (`app/Repositories/LodServicesRepository.php`) de diensten op en
maakt ze aan of werkt ze bij op basis van `source` + `identifier`.

| Bron | Graph | Selectie |
| --- | --- | --- |
| `vesta` | `http://stad.gent/agents/` | `foaf:Agent` met `dct:source` die begint met `VESTA` |
| `recreatex` | `http://stad.gent/agents/` | `foaf:Agent` met `dct:source` die begint met `RECREATEX` |
| `publicToilets` | `http://stad.gent/public-restrooms/` | `schema:PublicToilet` |
| `crm` | `http://stad.gent/agents-crm/` | `schema:Organization` met `dct:source` die begint met `CRM` |

Een VESTA-zakenpartner komt enkel in LOD (en dus in het platform) als
"Publiceren in LOD" aangevinkt is. Nieuwe diensten komen binnen als draft.

!!! note
    Ook het **lezen** gebeurt via het SPARQL-write-endpoint
    (`SPARQL_WRITE_*`). De variabelen `SPARQL_ENDPOINT*` en
    `SPARQL_WRITE_INSERT_ENDPOINT` in `.env.example` worden door de code niet
    gebruikt.

## Openingsuren naar LOD

`UpdateLodOpeninghours` schrijft per versie een RDF-graph via SPARQL Update
(eerst een DELETE van de oude triples, dan een `INSERT DATA`) naar de graph in
`SPARQL_WRITE_GRAPH`. `DeleteLodOpeninghours` en `DeleteLodChannel` verwijderen
triples.

- Vocabularia: `cv:` (`http://data.europa.eu/m8g/`), `oh:`
  (`http://semweb.datasciencelab.be/ns/oh#`) en iCal (`Vcalendar`, `Vevent`).
- URI's worden opgebouwd met `BASE_URI` (`/service/{id}`, `/channel/{id}`,
  `/openinghours/{id}`, ...) in `app/helpers.php`. Kanalen verwijzen met
  `rdfs:isDefinedBy` naar `DATA_REPRESENTATION_URI/channel/{id}`.

## VESTA

VESTA is het CRM met de zakenpartners van de stad. Het platform schrijft een
tekstuele (HTML) weergave van de openingsuren naar het veld
`ves_openingsuren` van de zakenpartner, zodat bestaande koppelingen vanuit
VESTA (o.a. InfoFiches) blijven werken.

- SOAP-webservice op `VESTA_ENDPOINT` (`?wsdl`), API-key `VESTA_KEY` als
  SOAP-header `X-API-Key`.
- Operaties: `SearchJSON` (huidige waarde lezen), `FillHours` (schrijven),
  `EmptyHours` (leegmaken).
- `UpdateVestaOpeninghours` stuurt de openingsuren van vandaag tot en met
  2 maanden later (`config('app.vesta_output_months')`), opgemaakt door
  `RecurringOHService`. Als de waarde niet gewijzigd is, wordt niets
  geschreven.
- Enkel voor diensten met bron `vesta`; de `identifier` is de VESTA-GUID.
- `VESTA_SOURCE_URL` wordt enkel in de UI gebruikt om naar de zakenpartner in
  VESTA te linken (placeholder `${identifier}`).

!!! warning
    Omdat VESTA een venster van 2 maanden krijgt, moet
    `openinghours:update-vesta` regelmatig draaien (bv. wekelijks), anders
    raakt de tekst in VESTA verouderd ook als er niets in het platform
    wijzigt. Zie [Beheer](beheer.md#geplande-taken).

## RecreateX

`openinghours:fetch-recreatex` importeert de openingsuren van sportlocaties
(diensten met bron `recreatex`) uit de RecreateX-webshop (SOAP op
`RECREATEX_URI`).

- `FindInfrastructureOpenings` met `SHOP_ID`, voor het huidige jaar tot en met
  3 jaar later. De openingen komen in een kanaal `CHANNEL_NAME` en kalender
  `CALENDAR_NAME`.
- RecreateX bewaart elke dag apart; het command zet die om naar wekelijkse
  RRULEs.
- Voor elke UUID in `SPORTS_UUIDS` (komma-gescheiden) haalt `FindReservations`
  per maand de reservaties op en maakt per activiteit een kanaal (type
  "Algemeen", gewicht `SPORTS_RESERVATION_CHANNEL_WEIGHT`).
- Kanalen die niet meer verwerkt werden, worden leeggemaakt.

## Feestdagen en vakanties

`openinghours:fetch-od-holidays` laadt de iCal-bestanden uit
`config/app.php` (`supported_exception_callendars`: feestdagen,
schoolvakanties, Gentse Feesten, extra sluitingsdagen Stad Gent) in de tabellen
`default_calendars` en `default_events`. De UI biedt ze aan als presets
(`GET /api/v1/ui/presets`).
