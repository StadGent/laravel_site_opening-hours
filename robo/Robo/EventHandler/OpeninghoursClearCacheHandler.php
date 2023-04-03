<?php

namespace Openinghours\Robo\EventHandler;

use DigipolisGent\Robo\Laravel\EventHandler\ClearCacheHandler;
use Symfony\Component\EventDispatcher\GenericEvent;

class OpeninghoursClearCacheHandler extends ClearCacheHandler
{
    use \DigipolisGent\Robo\Task\Deploy\Tasks;

    public function getPriority(): int
    {
        return parent::getPriority() + 100;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $remoteConfig = $event->getArgument('remoteConfig');
        $remoteSettings = $remoteConfig->getRemoteSettings();
        $currentWebRoot = $remoteSettings['currentdir'];
        $collection = $this->collectionBuilder();
        $auth = new KeyFile($remoteConfig->getUser(), $remoteConfig->getPrivateKeyFile());

        $currentProjectRoot = $currentWebRoot . '/..';
        // Restart the queue after clearing cache.
        $collection->taskSsh($remoteConfig->getHost(), $auth)
            ->remoteDirectory($currentProjectRoot, true)
            ->timeout(120)
            ->exec('php artisan queue:restart');

        return $collection;
    }
}
