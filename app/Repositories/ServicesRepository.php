<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\User;

class ServicesRepository extends EloquentRepository
{
    public function __construct(Service $service)
    {
        parent::__construct($service);
    }

    /**
     * Return all services with their channels and openinghours (without the attached calendars)
     *
     * @return Collection
     */
    public function get()
    {
        return $this->model->get()->map(array($this, 'expandService'));
    }

    /**
     * Get all services where the user, based on the passed user ID, is part of
     *
     * @param  integer    $userId
     * @return Collection
     */
    public function getForUser($userId)
    {
        return $this->model->whereHas('users', function ($query) use ($userId) {
            $query->where('id', '=', $userId);
        })->get()->map(array($this, 'expandService'));
    }

    /**
     * Get the complete service detail
     *
     * @param  integer $userId
     * @return array
     */
    public function expandService($service)
    {
        $result = $service->toArray();
        $result['channels'] = [];

        // Get all of the channels for the service
        foreach ($service->channels as $channel) {
            $tmpChannel = $channel->toArray();
            $tmpChannel['openinghours'] = [];

            foreach ($channel->openinghours as $openinghours) {
                $tmpChannel['openinghours'][] = $openinghours->toArray();
            }

            $result['channels'][] = $tmpChannel;
        }

        $users = app('UserRepository');

        // Get all of the users for the service
        $result['users'] = $users->getAllInService($result['id']);

        return $result;
    }
}
