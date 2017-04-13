# Openinghours

# Set up
## Installation
Copy the .env.example to .env and

- Fill in the MySQL (or MariaDb) database credentials
- Fill in the VESTA API configuration
- Fill in the Queue driver, for production environments use redis, beanstalkd or SQS. DO NOT use sync as a queue, rather use database in testing environments
- Fill in the base URI that is used to build the LOD version of the openinghours
- Fill in the SPARQL configuration to read data from
- Fill in the SPARQL configuration to write data to, don't forget the name of the graph
- Mails are sent through sendgrid, if available use an API key, if not implement a version of AppMailer and create a binding in the IoC
- Set session driver to database
- Build the back & front-end

    composer install
    artisan migrate
    artisan db:seed # This will generate an admin user with a random generated password that's outputted to the command line, the default email is admin@foo.bar.

    npm install
    gulp build

## Fetch services

In order to fetch a list of services from the SPARQL endpoint you'll need to configure the SPARQL endpoint (READ). You can then run the following command:

    > php artisan openinghours:fetch-services

This will fill the services table with the identifiers, labels of the available services from the SPARQL endpoint.

## Fetch recreatex

In order to add openinghours from the Recreatex application to services that are present in the Recreatex application, you can run the following command, after configuring the Recreatex variables in the .env file:

    > php artisan openinghours:fetch-recreatex

Note: this will import openinghours from 2017 up until 2020.

## Email

Email is now done through SendGrid, simply add an API key to the .env variable.

# Usage

## Queries - No APIB or Swagger available

- The serviceUri parameter is required and must be the URI of a service
- The channel parameter is optional and must be the name of a specific channel within the given service
- The format is optional and by default will return a JSON result, other formats can be: html, text, json-ld

### Get the schedule for the next 7 days

The URI template to get the openinghours for a certain service and a channel within that service (optional).
If no channel is passed, all channels will be returned with a schedule of the coming 7 days.

- {host}/api/query?q=week&serviceUri={serviceUri}&channel={channel}&format={format}

### Get the schedule for this week

- {host}/api/query?q=this-week&serviceUri={serviceUri}&channel={channel}&format={format}

### Is something open right now?

- {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}&format={format}

### Get the openinghours for a specific day

- {host}/api/query?q=day&date={mm-dd-yyyy}&serviceUri={serviceUri}&channel={channel}&format={format}