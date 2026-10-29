# Openinghours

Platform to manage opening hours for services and their channels.

The full (Dutch) functional and technical documentation lives in
[`docs/`](docs/index.md) and is published in Backstage.

[![Tests](https://github.com/StadGent/laravel_site_opening-hours/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/StadGent/laravel_site_opening-hours/actions/workflows/tests.yml?query=branch%3Amaster)

## Installation

### Create config file

Copy the .env.example to .env and

- Fill in the `MySQL` (or MariaDb) database credentials.
- Fill in the `VESTA API` configuration (make sure it's not base64 encoded).
- Fill in the `VESTA_SOURCE_URL` for the links to vesta services (use a 
  `${identifier}` placeholder).
- Fill in the Queue driver, for production environments use redis, beanstalkd or
  SQS. DO NOT use sync as a queue, rather use database in testing environments
  (`https://laravel.com/docs/5.4/queues`).
- Fill in the base URI that is used to build the LOD version of the openinghours
  triple (`BASE_URI`).
- Fill in the base URI that is used to link to a data representation of
  something (`DATA_REPRESENTATION_URI`).
- Fill in the `SPARQL_WRITE_*` configuration, don't forget the name of the
  graph. This endpoint is used both to read the services and to write the
  opening hours (the `SPARQL_ENDPOINT*` variables are not used).
- Mails are sent through SMTP.
- Set session driver to database (anything but file).
- Set 1 worker in supervisor
  (`https://laravel.com/docs/5.4/queues#supervisor-configuration`) to handle the
  writes to the different systems (VESTA, LOD). Only using 1 will make sure no
  dirty read/writes will happen to the SPARQL endpoint, you can use supervisor
  or a custom system that keeps a Laravel worker up and running. Make sure that
  the worker is restarted after an update on the software 
  (`php artisan queue:restart`).

### Build the back & front-end

Build the back & front-end by running following commands:

```bash
composer install
./artisan migrate
./artisan db:seed
./artisan passport:keys
npm install
gulp build
```

The `./artisan db:seed` command will generate an admin user with a random
generated password that's outputted to the command line, the default email is
admin@foo.bar.

### Super admin

One user is the super admin, set with `SUPER_ADMIN_ID` in `.env` (default `1`,
the admin user created by `./artisan db:seed`). This is a convention of this
application, not of Laravel. Only the super admin can, through the UI:

- grant or revoke the global Admin role,
- invite someone as Admin, or invite an existing Admin with another role,
- delete an Admin.

Other admins can still manage all non-admin users. Make sure the id belongs to
an existing user with the Admin role. The rules live in
`app/Policies/UserPolicy.php`.

## Maintenance and deployment

When updating the software the commands you'll want to run are the following:

```bash
composer install
gulp build
php artisan migrate
php artisan cache:clear
php artisan queue:restart
```

## Fetch services

In order to fetch a list of services from the SPARQL endpoint you'll need to
configure the SPARQL endpoint (`SPARQL_WRITE_*`). You can then run the
following command:

```bash
php artisan openinghours:fetch-services
```

This will fill the services table with the identifiers, labels of the available
services from the SPARQL endpoint.

## Fetch recreatex

In order to add openinghours from the Recreatex application to services that are
present in the Recreatex application, you can command below, after configuring
the Recreatex variables in the .env file.

This command will fetch services that are available in the application and have
a recreatex source, a property fetched during the retrieval of services from the
SPARQL endpoint using the fetch-services command.

The recreatex command will fetch services using the configured recreatex
endpoint and shop-id and will do so, year by year, from the current year up to
3 years ahead. These events will be added to the channel configured in
`CHANNEL_NAME`.

Because Recreatex doesn't save it's events in a calendar standard, rather it
saves every single day as an array of 2 events (maximum).

This would result in 365 events per channel, which is far from optimal to work
with without additional tweaking, therefore a small algorithm was written that
parses weekly RRULEs from the yearly event list.

```bash
php artisan openinghours:fetch-recreatex
```

> **Note**: make sure you refresh the application in the browser after fetching
> the recreatex openinghours, because the front-end caches responses by default.

## Email

Email is now done through SMTP

## VESTA

Changes to opening hours of VESTA services are pushed to VESTA through the
queue. The output contains the opening hours from today up to 2 months ahead,
so run the command below regularly (e.g. weekly through cron, it is not part of
the Laravel scheduler) to keep VESTA up to date.

```bash
php artisan openinghours:update-vesta
```

## Usage

The public API lives under `/api/v1` and doesn't require authentication. The
format is chosen with the `Accept` header (`application/json`,
`application/ld+json`, `text/html` or `text/plain`).

- `{host}/api/v1/services[/{service}[/channels[/{channel}]]]`
- `{host}/api/v1/services/{service}[/channels/{channel}]/openinghours?from={date}&until={date}`
- `{host}/api/v1/services/{service}[/channels/{channel}]/openinghours/{day|week|month|year}?date={date}`
- `{host}/api/v1/services/{service}[/channels/{channel}]/open-now`

See [docs/functioneel/api.md](docs/functioneel/api.md) for all parameters, and
the [SwaggerHub specification](https://app.swaggerhub.com/apis/Digipolis/Openinghours/1.0.0).

## Contributions

The first iteration codebase has been written by 
[weconnectdata](https://github.com/weconnectdata), more specifically by
[@thgh](https://github.com/thgh) and [@coreation](https://github.com/coreation).

The design and functional analysis has been performed by
[@mietcls](https://github.com/mietcls)

Code review and further maintenance are done by the District09 team.
