<?php

/**
 * Return the english abbreviation of a day passed in dutch
 * If no abbreviation is found, return the original day
 * @deprecated
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

/**
 * @deprecated
 * @param [type] $datetime
 * @return [type]
 */
function carbonize($datetime = null)
{
    if (empty($datetime)) {
        return \Carbon\Carbon::now();
    }

    return \Carbon\Carbon::createFromFormat('Y-m-d', $datetime);
}

/**
 * @deprecated
 * @param [type] $openinghoursId
 * @return [type]
 */
function createOpeninghoursUri($openinghoursId)
{
    return env('BASE_URI') . '/openinghours/' . $openinghoursId;
}

/**
 * @deprecated
 * @param [type] $openinghoursId
 * @param [type] $calendarId
 * @return [type]
 */
function createCalendarUri($openinghoursId, $calendarId)
{
    return env('BASE_URI') . '/openinghours/' . $openinghoursId . '/calendar/' . $calendarId;
}

/**
 * @deprecated
 * @param [type] $channelId
 * @return [type]
 */
function createChannelUri($channelId)
{
    return env('BASE_URI') . '/channel/' . $channelId;
}

/**
 * @deprecated
 * @param [type] $serviceId
 * @return [type]
 */
function createServiceUri($serviceId)
{
    $service = app('ServicesRepository')->getById($serviceId);

    if (empty($service)) {
        return env('BASE_URI') . '/service/' . $serviceId;
    }

    return $service['uri'];
}

/**
 * @deprecated
 * @param [type] $path
 * @return [type]
 */
function version($path)
{
    try {
        return $path . (strpos($path, '?') ? '&' : '?') . 'v=' . filemtime(public_path() . '/' . $path);
    } catch (\Exception $ex) {
        // Failsafe when filemtime should fail
        return $path;
    }
}

/**
 * @deprecated
 * @param [type] $obj
 * @param [type] &$result
 * @return [type]
 */
function normalizeSimpleXML($obj, &$result)
{
    $data = $obj;

    if (is_object($data)) {
        $data = get_object_vars($data);
    }

    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $res = null;
            normalizeSimpleXML($value, $res);
            if (($key == '@attributes') && ($key)) {
                $result = $res;
            } else {
                $result[$key] = $res;
            }
        }
    } else {
        $result = $data;
    }

    return json_encode($result);
}
