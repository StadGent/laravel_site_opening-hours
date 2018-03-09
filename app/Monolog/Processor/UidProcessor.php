<?php

namespace App\Monolog\Processor;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Processor that adds a uid to the extra key of a log record.
 */
class UidProcessor
{

    /**
       * Adds the uid to the record's extra key.
       *
       * @param array $record
       *
       * @return array
       */
    public function __invoke(array $record)
    {
        $record['extra']['uid'] = 0;
        $user = auth()->user();
        $uid = $user ? $user->getAuthIdentifier() : null;
        if (null === $uid) {
            return $record;
        }

        $record['extra']['uid'] = $uid;
        return $record;
    }
}
