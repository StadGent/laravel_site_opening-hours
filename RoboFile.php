<?php

define('NET_SSH2_LOGGING', \phpseclib\Net\SSH2::LOG_COMPLEX);

use DigipolisGent\Robo\Laravel\RoboFileBase;

class RoboFile extends RoboFileBase
{
    /**
     * @inheritdoc
     */
    protected function clearCacheTask($worker, $auth, $remote)
    {
        $currentProjectRoot = $remote['currentdir'] . '/..';
        $collection = $this->collectionBuilder();
        $collection->addTask(parent::clearCacheTask($worker, $auth, $remote));
        // Restart the queue after clearing cache.
        $collection->taskSsh($worker, $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->timeout(120)
            ->exec('php artisan queue:restart');
        return $collection;
    }
}
