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

    /**
     * Run Tests.
     */
    public function test()
    {
        $this->stopOnFail(true);
        $this->taskPHPUnit()
            ->option('disallow-test-output')
            ->option('report-useless-tests')
            ->option('strict-coverage')
            ->option('-d error_reporting=-1')
            ->option('--coverage-clover=build/logs/clover.xml')
            ->arg('tests')
            ->run();
    }

    /**
     * Install precommit hook.
     */
    public function precommitInstall()
    {
        // Create the git/hooks symlinks.
        $files = array_diff(scandir(__DIR__ . '/.git-hooks'), ['..', '.']);
        foreach ($files as $file) {
            $this->say(sprintf('Add git hook : %s.', $file));

            // Only if symlink does not exists yet.
            $to = '.git/hooks/' . $file;
            if (file_exists($to)) {
                $this->say('✔ Already exists.');
                continue;
            }
            $this->taskFilesystemStack()
                ->symlink(__DIR__ . '/.git-hooks/' . $file, $to)
                ->run();
            $this->say('✔ Created symlink.');
        }
    }
}
