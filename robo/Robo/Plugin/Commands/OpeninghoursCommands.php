<?php

namespace Openinghours\Robo\Plugin\Commands;

use Openinghours\Robo\EventHandler\OpeninghoursClearCacheHandler;
use Robo\Tasks;

class OpeninghoursCommands extends Tasks
{

    /**
     * @hook on-event digipolis:clear-cache
     */
    public function getClearCacheHandler()
    {
        return new OpeninghoursClearCacheHandler();
    }
}
