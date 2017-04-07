<?php

/**
 * Return the english abbreviation of a day passed in dutch
 * If no abbreviation is found, return the original day
 *
 * @param  string $day
 * @return string
 */
function codeForDay($day)
{
    $mapping = [
        'maandag' => 'Mo',
        'dinsdag' => 'Tu',
        'woensdag' => 'We',
        'donderdag' => 'Th',
        'vrijdag' => 'Fr',
        'zaterdag' => 'Sa',
        'zondag' => 'Su',
    ];

    $code = @$mapping[strtolower($day)];

    if (empty($code)) {
        return $day;
    }

    return $code;
}

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

function version($path)
{
    try {
        return $path . (strpos($path, '?') ? '&' : '?') . 'v=' . filemtime(public_path() . '/' . $path);
    } catch (\Exception $ex) {
        // Failsafe when filemtime should fail
        return $path;
    }
}