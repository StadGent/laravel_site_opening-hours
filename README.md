# Openinghours

# Set up
## Installation
Copy the .env.example to .env and

- Fill in the MySQL (or MariaDb) database credentials
- Fill in the VESTA API configuration
- Fill in the Queue driver, for production environments use redis, beanstalkd or SQS. Do not use database or sync
- Fill in the base URI that is used to build the LOD version of the openinghours
- Fill in the SPARQL configuration to read data from
- Fill in the SPARQL configuration to write data to, don't forget the name of the graph
- Mails are sent through sendgrid, if available use an API key, if not implement a version of AppMailer and create a binding in the IoC
- Set session driver to database
- Build the back & front-end

    composer install
    artisan migrate
    artisan db:seed

    npm install
    gulp build

## Fetch services

In order to fetch services from the SPARQL endpoint (first configure the endpoint in the .env), you can run the following command:

    > php artisan openinghours:fetch-services

# Usage

## Queries - No APIB or Swagger available

### Get the schedule for the next 7 days

The URI template to get the openinghours for a certain service and a channel within that service (optional).
If no channel is passed, all channels will be returned with a schedule of the coming 7 days.

- {host}/api/query?q=week&serviceUri={serviceUri}&channel={channel}

## Is something open right now?

- {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}
