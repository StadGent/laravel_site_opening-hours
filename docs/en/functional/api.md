# Public API

The public API requires no authentication and is available under `/api/v1`.
The specification is on
[SwaggerHub](https://app.swaggerhub.com/apis/Digipolis/Openinghours/1.0.0).

!!! note
    The QA and test environments are behind basic auth.

## Endpoints

| Endpoint | Description |
| --- | --- |
| `GET /services` | All active services. Filters: `label` (contains), `uri`, `source`, `sourceId` |
| `GET /services/{service}` | One service |
| `GET /services/{service}/channels` | Channels of a service |
| `GET /services/{service}/channels/{channel}` | One channel |
| `GET /types` | All channel types |

Opening hours can be requested for all channels of a service
(`/services/{service}/...`) or for one channel
(`/services/{service}/channels/{channel}/...`):

| Path | Parameters | Description |
| --- | --- | --- |
| `.../openinghours` | `from`, `until` (required, max. 366 days) | Opening hours in a period |
| `.../openinghours/day` | `date` | Opening hours on one day |
| `.../openinghours/week` | `date` | Week containing `date` |
| `.../openinghours/month` | `date` | Month containing `date` |
| `.../openinghours/year` | `date` | Year containing `date` |
| `.../open-now` | `testDateTime` (optional) | Is the service / channel open right now? |

Example:

```bash
curl -H 'Accept: application/json' \
  'https://openingsuren.gent.be/api/v1/services/{service}/channels/{channel}/openinghours/day?date=2026-11-02'
```

The service and channel IDs are the platform's IDs; you find them in the URL of
the management interface or through `GET /services?sourceId=...`.

## Formats

The format is chosen **only** through the `Accept` header:

| `Accept` | Result |
| --- | --- |
| `application/json` | JSON |
| `application/ld+json` | JSON-LD (schema.org) |
| `text/html` | HTML fragment with RDFa, ready to show on a website |
| `text/plain` | Readable text |

Services, channels and types are only available as `application/json`. A
missing or unsupported `Accept` header returns `406 Not Acceptable`.

## Language

The language of days, months and holidays is set by `?language=` or the
`Accept-Language` header. Supported: Dutch, French, English, German and
Spanish. The first day of the week depends on the locale.

## Caching and limits

- `Cache-Control: max-age=900, public` (15 minutes); `open-now` is `private`.
- `Access-Control-Allow-Origin: *`
- Rate limit: 240 requests per minute per client.
- A draft service returns `404`.

## Consumers

Known consumers include stad.gent, Visit Gent, the sports website and RADAR
(school day and holiday checks through a "schoolday" and a "holiday" channel).
The PHP client
[`stadgent/services-opening-hours`](https://github.com/StadGent/php_package_services-opening-hours)
and the Drupal module
[`opening_hours`](https://github.com/StadGent/drupal_module_opening-hours)
use this API.
