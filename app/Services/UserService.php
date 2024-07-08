<?php

namespace App\Services;

use App\Mail\SendInviteConfirmation;
use App\Mail\SendRegisterConfirmation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
   * Attach a role to a user, handling admin and non-admin roles differently.
   *
   * @param User $user
   * @param Role $role
   */
  private function attachRoleToUser($user, $role)
  {
    if ($role->name === 'Admin') {
      $this->userRepository->removeLinksToAllServices($user->id);
      $user->attachRole($role);
    } else {
      $adminRole = Role::where('name', 'Admin')->first();
      $user->detachRole($adminRole);
    }
  }

  /**
   * Link user to services based on role, if applicable.
   *
   * @param User $user
   * @param Service $service
   * @param Role $role
   */
  private function linkUserToService($user, $service, $role)
  {
    if($role->name !== "Admin") {
      $this->userRepository->linkToService($user->id, $service->id, $role->name);
    }
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
    $input['token'] = Str::random(32);

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
   * Set multiple roles to user, refactored to be more DRY.
   *
   * Admin needs to be set in role_user table
   * others need to be set in user_service_role
   *
   * @param string email
   * @param Role $role
   * @param array|Illuminate\Database\Eloquent\Collection $services
   */
  public function setRolesToUser($email, Role $role, Collection $services = null)
  {
    $user = User::where('email', $email)->firstOrCreate(['email' => $email]);

    $newUser = $user->wasRecentlyCreated;

    $this->attachRoleToUser($user, $role);

    foreach ($services as $service) {
      $this->linkUserToService($user, $service, $role, $newUser);
    }

    if (!$newUser) {
      // Send mail with overview of added services.
      Mail::to($user)->send(new SendInviteConfirmation($user, $services));
    }

    $user->role = $role->name;

    return $user;
  }
}
