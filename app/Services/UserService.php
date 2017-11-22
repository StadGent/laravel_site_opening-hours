<?php

namespace App\Services;

use App\Mail\SendInviteConfirmation;
use App\Mail\SendRegisterConfirmation;
use App\Models\Channel;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Mail;

/**
 * Internal Business logic Service for User
 */
class UserService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Singleton class instance.
     *
     * @var ChannelService
     */
    private static $instance;

    /**
     * Private contructor for Singleton pattern
     */
    private function __construct()
    {
        $this->userRepository = app('UserRepository');
    }

    /**
     * GetInstance for Singleton pattern
     *
     * @return ChannelService
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        self::$instance->serviceModel = null;

        return self::$instance;
    }

    /**
     * Create New user
     *
     * capture of old logic
     * + add SendRegisterConfirmation
     *
     * @param [type] $email
     * @return User
     */
    public function createNewUser($email)
    {
        $input['password'] = '';
        $input['token'] = str_random(32);

        $input['email'] = $email;
        $input['name'] = $email;

        $userId = $this->userRepository->store($input);
        $user = $this->userRepository->getById($userId);
        if (!$user) {
            throw new Exception('Something went wrong while storing the user, check the logs.', 400);
        }

        Mail::to($user)->send(new SendRegisterConfirmation($user));

        return $user;
    }

    /**
     * Set role to user
     *
     * Admin needs to be set in role_user table
     * others need to be set in user_service_role
     *
     * @param string email
     * @param Role $role
     * @param Service $servcie
     */
    public function setRoleToUser($email, Role $role, Service $service = null)
    {
        $user = User::where('email', $email)->first();

        if ($newUser = $user === null) {
            $user = $this->createNewUser($email);
        }

        if ($role->name === 'Admin') {
            $this->userRepository->removeLinksToAllServices($user->id);
            $user->attachRole($role);
        } else {
            $adminRole = Role::where('name', 'Admin')->first();
            $user->detachRole($adminRole);
            $this->userRepository
                ->linkToService($user->id, $service->id, $role->name);
            if (!$newUser) {
                Mail::to($user)->send(new SendInviteConfirmation($user, $service));
            }
        }

        $user->role = $role->name;

        return $user;
    }
}
