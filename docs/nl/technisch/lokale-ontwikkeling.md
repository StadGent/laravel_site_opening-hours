# Lokale ontwikkeling

## Vereisten

- PHP 8.3 met de extensies `soap`, `intl`, `pdo_mysql`
- Composer
- MySQL 8 of MariaDB
- Node.js 16 (zie `.nvmrc`) en npm
- Een webserver (Apache/nginx) met de document root op `public/`, of
  `php artisan serve`

## Installatie

```bash
git clone git@github.com:StadGent/laravel_site_opening-hours.git
cd laravel_site_opening-hours
cp .env.example .env            # vul DB_* en de andere variabelen in
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed             # maakt admin@foo.bar met een willekeurig wachtwoord
php artisan passport:keys
npm install
npm run build && npm run sass
```

`db:seed` toont het wachtwoord van de admin in de console.

Diensten ophalen kan enkel met toegang tot het SPARQL-endpoint
(`openinghours:fetch-services`). Zonder die toegang kan je diensten manueel in
de tabel `services` toevoegen (zet `draft` op `0` om ze in de publieke API te
zien).

!!! tip "Externe systemen"
    Zet lokaal `QUEUE_CONNECTION=database` en start geen worker, dan blijven de
    sync-jobs naar VESTA en LOD in de tabel `jobs` staan in plaats van te
    falen. `sync` voert ze onmiddellijk uit tijdens het request.

## Frontend

De frontend (Vue 2) staat in `resources/assets/js` en wordt met Rollup
gebundeld naar `public/js/bundle.js`; Sass compileert naar
`public/css/app.css`.

| Command | Doel |
| --- | --- |
| `npm run build` | Eenmalige JS-build |
| `npm run dev` | JS-build in watch-mode |
| `npm run sass` | Eenmalige CSS-build |
| `npm run devsass` | CSS in watch-mode |

`gulp build` en `gulp watch` roepen dezelfde npm-scripts aan.

## Tests

```bash
cp .env.example .env.testing   # met een aparte testdatabase
vendor/bin/phpunit
```

De tests voor `VestaService` en `SparqlService` spreken de echte endpoints
aan. Zet `APP_SKIP_TRAVIS_TEST=true` om ze over te slaan.
