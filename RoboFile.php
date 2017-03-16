<?php

define('NET_SSH2_LOGGING', \phpseclib\Net\SSH2::LOG_COMPLEX);

use DigipolisGent\Robo\Laravel\RoboFileBase;

class RoboFile extends RoboFileBase
{
    /**
     * TODO: remove this method once the gulp task is fixed.
     */
    protected function buildTask($archivename = null)
    {
        $archive = is_null($archivename) ? $this->time . '.tar.gz' : $archivename;
        $collection = $this->collectionBuilder();
        $collection
            ->taskPackageProject($archive)
                ->ignoreFileNames([
                    '.env.example',
                    '.gitattributes',
                    '.gitignore',
                    'README',
                    'README.txt',
                    'README.md',
                    'LICENSE',
                    'LICENSE.txt',
                    'LICENSE.md',
                ]);
        return $collection;
    }
}
