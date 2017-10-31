<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use DB;

class UserRepository extends EloquentRepository
{
    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    /**
     * @param array $input
     * @return mixed
     */
    public function store(array $input)
    {
        $existingUser = $this->model->where('email', $input['email'])->first();

        if (empty($existingUser)) {
            $input = array_only($input, $this->model->getFillable());

            return parent::store($input);
        }

        return $existingUser->id;
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getById($userId)
    {
        $user = $this->model->find($userId);

        if (empty($user)) {
            return [];
        }

        // Get all of the roles for the services where the user is part of
        $roles = $this->getAllRolesForUser($userId);

        $user['roles'] = $roles;

        return $user;
    }

    /**
     * Return all users with their roles
     *
     * @param  integer $limit
     * @param  integer $offset
     * @return array
     */
    public function getAll($limit = 50, $offset = 0)
    {
        $users = $this->model->take($limit)->skip($offset)->get();

        $results = [];

        foreach ($users as $user) {
            $result = $user->toArray();
            $result['roles'] = $this->getAllRolesForUser($user->id);

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Give a user a specific role in a certain service
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @param  string  $role      The name of the role
     * @return boolean
     */
    public function linkToService($userId, $serviceId, $role)
    {
        $roleId = $this->getRoleId($role);

        if (!empty(
            DB::select(
                'SELECT role_id FROM user_service_role WHERE user_id = ? AND service_id = ?',
                [$userId, $serviceId]
            )
        )
        ) {
            DB::connection()->enableQueryLog();

            return DB::update(
                'UPDATE user_service_role SET role_id = ? WHERE  user_id = ? AND service_id = ?',
                [$roleId, $userId, $serviceId]
            );
        } else {
            return DB::insert(
                'INSERT INTO user_service_role (user_id, role_id, service_id) VALUES (?, ?, ?)',
                [$userId, $roleId, $serviceId]
            );
        }
    }

    /**
     * Get all of the roles of a user
     *
     * @param  integer $userId
     * @return array
     */
    private function getAllRolesForUser($userId)
    {
        $results = DB::select(
            'SELECT * FROM user_service_role WHERE user_id = ?',
            [$userId]
        );

        $roles = [];

        foreach ($results as $result) {
            $role = Role::find($result->role_id);

            $roles[] = [
                'role' => $role->name,
                'service_id' => $result->service_id,
            ];
        }

        return $roles;
    }

    /**
     * Check if a user has a role in a service
     *
     * @param  integer $userId
     * @param  integer $serviceId
     * @param  string  $role      The name of the role
     * @return boolean
     */
    public function hasRoleInService($userId, $serviceId, $role)
    {
        return $this->getRoleInService($userId, $serviceId) == $role;
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

        if (!empty($result)) {
            $result = array_shift($result);
            $role = Role::find($result->role_id);

            return $role['name'];
        }
    }

    /**
     * Get all users in a service
     *
     * @param  integer $serviceId
     * @return array
     */
    public function getAllInService($serviceId)
    {
        $results = DB::select(
            'SELECT users.name, users.id, users.email, roles.name as role, if(length(users.password)<10, 0, 1) as verified
            FROM user_service_role
            JOIN users ON users.id = user_service_role.user_id
            JOIN roles ON roles.id = user_service_role.role_id
            WHERE user_service_role.service_id = ?',
            [$serviceId]
        );

        $users = [];

        foreach ($results as $result) {
            $users[] = (array) $result;
        }

        return $users;
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
        return DB::delete('DELETE FROM user_service_role WHERE user_id = ? AND service_id = ?', [$userId, $serviceId]);
    }

    /**
     * @param $role
     * @return mixed
     */
    private function getRoleId($role)
    {
        $role = Role::where('name', $role)->firstOrFail();

        return $role->id;
    }
}
