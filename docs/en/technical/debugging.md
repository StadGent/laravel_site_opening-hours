# Debugging

## Opening hours are wrong

- Check that the calendars are **published**: unpublished calendars don't
  count.
- Check the **order of the layers**: the top layer wins (see
  [Managing opening hours](../functional/opening-hours.md#calendars-layers)).
- Check that the version covers the requested date.
- Request the week as text for a quick comparison:

```bash
curl -H 'Accept: text/plain' \
  'https://openingsuren.gent.be/api/v1/services/{service}/channels/{channel}/openinghours/week?date=2026-11-02'
```

- Websites and the API cache for up to 15 minutes.

## VESTA shows old opening hours

```bash
php artisan openinghours:print-vesta-output {service-id}
```

shows the text that would be sent to VESTA. Then check:

- that the service has source `vesta` and is not a draft;
- that the queue worker is running and whether there are failed jobs (see
  below);
- that `openinghours:update-vesta` ran recently (the text in VESTA only covers
  2 months).

## A service is missing

- Is the service in LOD? `php artisan openinghours:print-lod-input vesta` (or
  `recreatex`, `publicToilets`, `crm`) shows the number of services found.
- Is "Publiceren in LOD" checked in VESTA?
- Run `php artisan openinghours:fetch-services` and check that the queue worker
  processes the `FetchServices` job.
- New services are drafts and must be activated by an Admin.

## Queue

```bash
php artisan queue:failed           # list failed jobs
php artisan queue:retry all        # retry them
php artisan queue:restart          # restart the worker after a deploy
```

The `queued_jobs` table tracks which sync jobs are already queued. If a job is
stuck and never gets queued again, check for an old row for that service.

## Logs

The application logs to syslog with ident `openingsuren`. On the servers the
logs are in Kibana. Locally you can log to a file with `LOG_CHANNEL=single`
(`storage/logs/laravel.log`).

## Content Security Policy

If the browser reports CSP errors, check
`app/Csp/Presets/OpeningHoursPreset.php`. You can disable it temporarily
locally with `CSP_ENABLED=false`.
