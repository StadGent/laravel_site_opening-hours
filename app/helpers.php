<?php

function carbonize($datetime = null)
{
    if (empty($dateTime)) {
        return \Carbon\Carbon::now();
    }

    return \Carbon\Carbon::createFromFormat('Y-m-d', $dateTime);
}
