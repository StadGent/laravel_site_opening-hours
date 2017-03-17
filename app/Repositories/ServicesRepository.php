<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;

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
     * Return a specific service with linked channels
     *
     * @param  int   $serviceId
     * @return array
     */
    public function getById($serviceId)
    {
        $service = $this->model->find($serviceId);

        if (empty($service)) {
            return [];
        }

        return $this->expandService($service);
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
                $instance = $openinghours->toArray();
                $instance['active'] = 0;

                 $endDate = Carbon::now()->addDay();

                if (! empty($instance['end_date'])) {
                    $endDate = Carbon::createFromFormat('Y-m-d', $instance['start_date']);
                }

                if (Carbon::now()->between(
                    Carbon::createFromFormat('Y-m-d', $openinghours['start_date']),
                    $endDate
                )) {
                    $instance['active'] = 1;
                }

                $tmpChannel['openinghours'][] = $instance;
            }

            $result['channels'][] = $tmpChannel;
        }

        $users = app('UserRepository');

        // Get all of the users for the service
        $result['users'] = $users->getAllInService($result['id']);

        return $result;
    }
}
