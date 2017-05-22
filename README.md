# Openinghours

## Installation
    composer install
    artisan migrate
    artisan db:seed

    npm install

## Queries

### Get the schedule for the next 7 days

The URI template to get the openinghours for a certain service and a channel within that service (optional).
If no channel is passed, all channels will be returned with a schedule of the coming 7 days.

- {host}/api/query?q=week&serviceUri={serviceUri}&channel={channel}

## Is something open right now?

- {host}/api/query?q=now&serviceUri={serviceUri}&channel={channel}

## Contributions

The first iteration codebase has been written by [weconnectdata](https://github.com/weconnectdata), more specifically by [@thgh](https://github.com/thgh) and [@coreation](https://github.com/coreation).
The design and functional analysis has been performed by [@mietcls](https://github.com/mietcls)

Codeclimate set-up, code review and further maintenance will be done by [@daften](https://github.com/daften) and the Digipolis team.
