# Publieke API

De publieke API vereist geen authenticatie en is beschikbaar onder
`/api/v1`. De specificatie staat op
[SwaggerHub](https://app.swaggerhub.com/apis/Digipolis/Openinghours/1.0.0).

!!! note
    De QA- en testomgeving staan achter basic auth.

## Endpoints

| Endpoint | Beschrijving |
| --- | --- |
| `GET /services` | Alle actieve diensten. Filters: `label` (bevat), `uri`, `source`, `sourceId` |
| `GET /services/{service}` | Eén dienst |
| `GET /services/{service}/channels` | Kanalen van een dienst |
| `GET /services/{service}/channels/{channel}` | Eén kanaal |
| `GET /types` | Alle kanaaltypes |

Openingsuren kunnen opgevraagd worden voor alle kanalen van een dienst
(`/services/{service}/...`) of voor één kanaal
(`/services/{service}/channels/{channel}/...`):

| Pad | Parameters | Beschrijving |
| --- | --- | --- |
| `.../openinghours` | `from`, `until` (verplicht, max. 366 dagen) | Openingsuren in een periode |
| `.../openinghours/day` | `date` | Openingsuren op één dag |
| `.../openinghours/week` | `date` | Week waarin `date` valt |
| `.../openinghours/month` | `date` | Maand waarin `date` valt |
| `.../openinghours/year` | `date` | Jaar waarin `date` valt |
| `.../open-now` | `testDateTime` (optioneel) | Is de dienst / het kanaal nu open? |

Voorbeeld:

```bash
curl -H 'Accept: application/json' \
  'https://openingsuren.gent.be/api/v1/services/{service}/channels/{channel}/openinghours/day?date=2026-11-02'
```

De ID's van diensten en kanalen zijn de ID's uit het platform; je vindt ze in de
URL van de beheerinterface of via `GET /services?sourceId=...`.

## Formaten

Het formaat wordt **enkel** via de `Accept`-header gekozen:

| `Accept` | Resultaat |
| --- | --- |
| `application/json` | JSON |
| `application/ld+json` | JSON-LD (schema.org) |
| `text/html` | HTML-fragment met RDFa, klaar om in een website te tonen |
| `text/plain` | Leesbare tekst |

Diensten, kanalen en types zijn enkel in `application/json` beschikbaar. Een
ontbrekende of niet-ondersteunde `Accept`-header geeft `406 Not Acceptable`.

## Taal

De taal van dagen, maanden en feestdagen wordt bepaald door `?language=` of de
`Accept-Language`-header. Ondersteund: Nederlands, Frans, Engels, Duits en
Spaans. De eerste dag van de week hangt af van de locale.

## Caching en limieten

- `Cache-Control: max-age=900, public` (15 minuten); `open-now` is `private`.
- `Access-Control-Allow-Origin: *`
- Rate limit: 240 requests per minuut per client.
- Een dienst in draft geeft `404`.

## Afnemers

Gekende afnemers zijn onder meer stad.gent, Visit Gent, de sportwebsite en
RADAR (schooldag- en feestdagcontrole via een "schoolday"- en
"holiday"-kanaal). De PHP-client
[`stadgent/services-opening-hours`](https://github.com/StadGent/php_package_services-opening-hours)
en de Drupal-module
[`opening_hours`](https://github.com/StadGent/drupal_module_opening-hours)
gebruiken deze API.
