# Openinghours

## Installation
    composer install
    artisan migrate
    artisan db:seed

    gulp build
    
## Fetch services

In order to fetch services from the SPARQL endpoint (first configure the endpoint in the .env), you can run the following command:

    > php artisan openinghours:fetch-services

## Queries

### Get the schedule for the next 7 days

The URI template to get the openinghours for a certain service and a channel within that service (optional).
If no channel is passed, all channels will be returned with a schedule of the coming 7 days.

- {host}/api/query?q=week&serviceUri={serviceUri}&channel={channel}

## Is something open right now?

- {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}
