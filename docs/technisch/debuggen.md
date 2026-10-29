# Debuggen

## Openingsuren kloppen niet

- Controleer of de kalenders **gepubliceerd** zijn: niet-gepubliceerde
  kalenders tellen niet mee.
- Controleer de **volgorde van de lagen**: de bovenste laag wint (zie
  [Openingsuren beheren](../functioneel/openingsuren.md#kalenders-lagen)).
- Controleer of de versie de gevraagde datum dekt.
- Vraag de dag op in tekst om snel te vergelijken:

```bash
curl -H 'Accept: text/plain' \
  'https://openingsuren.gent.be/api/v1/services/{service}/channels/{channel}/openinghours/week?date=2026-11-02'
```

- Websites en de API cachen tot 15 minuten.

## VESTA toont oude openingsuren

```bash
php artisan openinghours:print-vesta-output {service-id}
```

toont de tekst die naar VESTA zou gaan. Controleer daarna:

- of de dienst bron `vesta` heeft en niet in draft staat;
- of de queue worker draait en of er gefaalde jobs zijn (zie hieronder);
- of `openinghours:update-vesta` recent gedraaid heeft (de tekst in VESTA
  beslaat maar 2 maanden).

## Een dienst ontbreekt

- Staat de dienst in LOD? `php artisan openinghours:print-lod-input vesta`
  (of `recreatex`, `publicToilets`, `crm`) toont het aantal gevonden diensten.
- Is in VESTA "Publiceren in LOD" aangevinkt?
- Draai `php artisan openinghours:fetch-services` en controleer dat de queue
  worker de `FetchServices`-job verwerkt.
- Nieuwe diensten staan in draft en moeten door een Admin geactiveerd worden.

## Queue

```bash
php artisan queue:failed           # gefaalde jobs bekijken
php artisan queue:retry all        # opnieuw proberen
php artisan queue:restart          # worker herstarten na een deploy
```

De tabel `queued_jobs` houdt bij welke sync-jobs al in de queue staan. Als een
job vastzit en niet opnieuw ingepland wordt, kijk dan of er een oude rij voor
die dienst in staat.

## Logs

De applicatie logt naar syslog met ident `openingsuren`. Op de servers vind je
de logs in Kibana. Lokaal kan je naar een bestand loggen met
`LOG_CHANNEL=single` (`storage/logs/laravel.log`).

## Content Security Policy

Geeft de browser CSP-fouten, controleer dan
`app/Csp/Presets/OpeningHoursPreset.php`. Tijdelijk uitschakelen kan lokaal met
`CSP_ENABLED=false`.
