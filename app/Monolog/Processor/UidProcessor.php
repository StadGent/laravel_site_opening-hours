<?php

namespace App\Monolog\Processor;

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
        try {
            $auth = auth();
            if (!$auth) {
                return $record;
            }
            $user = $auth->user();
            $uid = $user ? $user->getAuthIdentifier() : null;
        } catch (\Exception $e) {
            // Do nothing. We could not load the current user.
        }
        if (null === $uid) {
            return $record;
        }

        $record['extra']['uid'] = $uid;
        return $record;
    }
}
