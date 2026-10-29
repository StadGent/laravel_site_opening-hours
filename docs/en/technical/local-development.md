# Local development

## Requirements

- PHP 8.3 with the `soap`, `intl` and `pdo_mysql` extensions
- Composer
- MySQL 8 or MariaDB
- Node.js 16 (see `.nvmrc`) and npm
- A web server (Apache/nginx) with the document root at `public/`, or
  `php artisan serve`

## Installation

```bash
git clone git@github.com:StadGent/laravel_site_opening-hours.git
cd laravel_site_opening-hours
cp .env.example .env            # fill in DB_* and the other variables
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed             # creates admin@foo.bar with a random password
php artisan passport:keys
npm install
npm run build && npm run sass
```

`db:seed` prints the admin's password to the console.

Fetching services requires access to the SPARQL endpoint
(`openinghours:fetch-services`). Without it, you can add services manually to
the `services` table (set `draft` to `0` to see them in the public API).

!!! tip "External systems"
    Set `QUEUE_CONNECTION=database` locally and don't start a worker: the sync
    jobs to VESTA and LOD then stay in the `jobs` table instead of failing.
    `sync` runs them immediately during the request.

## Frontend

The frontend (Vue 2) lives in `resources/assets/js` and is bundled with Rollup
into `public/js/bundle.js`; Sass compiles to `public/css/app.css`.

| Command | Purpose |
| --- | --- |
| `npm run build` | One-off JS build |
| `npm run dev` | JS build in watch mode |
| `npm run sass` | One-off CSS build |
| `npm run devsass` | CSS in watch mode |

`gulp build` and `gulp watch` call the same npm scripts.

## Tests

```bash
cp .env.example .env.testing   # with a separate test database
vendor/bin/phpunit
```

The tests for `VestaService` and `SparqlService` call the real endpoints. Set
`APP_SKIP_TRAVIS_TEST=true` to skip them.
