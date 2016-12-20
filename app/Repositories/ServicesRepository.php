<?php

namespace App\Repositories;

use App\Models\Service;
use DB;

class ServicesRepository extends EloquentRepository
{
    public function __construct(Service $service)
    {
        parent::__construct($service);
    }

    /**
     * Return all services with their channels and openinghours (without the attached calendars)
     *
     * @return array
     */
    public function get()
    {
        $services = $this->model->get();

        $results = [];

        foreach ($services as $service) {
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

            $users = app()->make('UserRepository');

            // Get all of the users for the service
            $result['users'] = $users->getAllInService($service->id);

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Get all services where the user is part of
     *
     * @param  integer $userId
     * @return array
     */
    public function getForUser($userId)
    {
        $services = DB::select(
            'SELECT label, uri, description
            FROM user_service_role
            JOIN services ON user_service_role.service_id = services.id
            WHERE user_id = ?',
            [$userId]
        );

        $results = [];

        foreach ($services as $service) {
            $results[] = (array) $service;
        }

        return $results;
    }
}
