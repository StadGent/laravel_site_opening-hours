<?php

namespace App\Repositories;

class UserRepository
{
    /**
     * Give a user a specific role in a certain service
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @param  integer $roleId
     * @return boolean
     */
    public function linkToService($userId, $serviceId, $roleId)
    {
        if (! empty(
            DB::select(
                'SELECT role_id FROM users_schools WHERE user_id = ? AND service_id = ?',
                [$userId, $serviceId]
            )
        )
        ) {
            DB::update(
                'UPDATE user_service_role SET user_id = ? WHERE  role_id = ? AND service_id = ?',
                [$userId, $roleId, $serviceId]
            );
        } else {
            DB::insert(
                'INSERT INTO user_service_role (user_id, role_id, service_id) VALUES (?, ?, ?)',
                [$userId, $roleId, $serviceI]
            );
        }
    }

    /**
     * Check if a user has a role in a service
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @return boolean
     */
    public function hasRoleInService($userId, $serviceId)
    {
        return ! empty($this->getRoleInService($userId, $serviceId));
    }

    /**
     * Get the role in a service for a user
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @return string  The name of the role
     */
    public function getRoleInService($userId, $serviceId)
    {
        $result = DB::select(
            'SELECT role_id FROM user_service_role WHERE user_id = ? AND service_id = ?',
            [$userId, $serviceId]
        );

        dd($result);
    }

    /**
     * Remove a role of a user in a certain service
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @return boolean
     */
    public function removeRoleInService($userId, $serviceId)
    {
        DB::delete('DELETE FROM user_service_role WHERE user_id = ? AND service_id = ?', [$userId, $serviceId]);
    }
}
