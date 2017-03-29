<?php

function carbonize($datetime = null)
{
    if (empty($dateTime)) {
        return \Carbon\Carbon::now();
    }

    return \Carbon\Carbon::createFromFormat('Y-m-d', $dateTime);
}

function createOpeninghoursUri($openinghoursId)
{
    return env('BASE_URI') . '/openinghours/' . $openinghoursId;
}

function createChannelUri($channelId)
{
    return env('BASE_URI') . '/channel/' . $channelId;
}

function createServiceUri($serviceId)
{
    return env('BASE_URI') . '/service/' . $serviceId;
}
