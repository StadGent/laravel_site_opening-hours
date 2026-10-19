<?php

namespace App\Policies;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Access\Response;

/**
 * Single place for the rules on who may manage which users and roles
 *
 * Abilities that only concern the acting user (administer, ownService,
 * manageAdmins) are checked against the User class, e.g.
 * $user->can('ownService', [User::class, $serviceId]).
 *
 * Denials keep the status codes the API already returned before these rules
 * were centralised: 401 for missing rights, 400 for altering yourself or an
 * Admin through a service role and 403 for managing admins.
 */
final class UserPolicy
{
    /**
     * @param UserRepository $users
     */
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * The user has the global Admin role
     *
     * @param User $actor
     * @return bool
     */
    public function administer(User $actor): bool
    {
        return $actor->hasRole('Admin');
    }

    /**
     * The user is the super admin (SUPER_ADMIN_ID) that may manage admins
     *
     * @param User $actor
     * @return bool
     */
    public function manageAdmins(User $actor): bool
    {
        return $actor->id === (int) config('auth.super_admin_id');
    }

    /**
     * The user may manage the users of the given service
     *
     * @param User $actor
     * @param int|string|null $serviceId
     * @return bool
     */
    public function ownService(User $actor, $serviceId): bool
    {
        return $this->administer($actor)
            || $this->users->hasRoleInService($actor->id, $serviceId, 'Owner');
    }

    /**
     * Delete a user
     *
     * @param User $actor
     * @param User $target
     * @return Response
     */
    public function delete(User $actor, User $target): Response
    {
        if ($this->isSelf($actor, $target)) {
            return Response::denyWithStatus(401, "You can't delete yourself!");
        }
        if (!$this->administer($actor)) {
            return Response::denyWithStatus(401, 'Unauthorized as admin');
        }
        if ($this->administer($target) && !$this->manageAdmins($actor)) {
            return Response::deny('Only the super admin can delete an Admin.');
        }

        return Response::allow();
    }

    /**
     * Set the global role (none, Editor or Admin) of a user
     *
     * @param User $actor
     * @param User $target
     * @param string|null $role
     * @return Response
     */
    public function updateGlobalRole(User $actor, User $target, ?string $role): Response
    {
        if ($this->isSelf($actor, $target)) {
            return Response::denyWithStatus(400, "You can't alter yourself!");
        }
        if (!$this->administer($actor)) {
            return Response::deny();
        }
        if (($role === 'Admin' || $this->administer($target)) && !$this->manageAdmins($actor)) {
            return Response::deny('Only the super admin can manage admins.');
        }

        return Response::allow();
    }

    /**
     * Set or remove (role null) the role of a user in a service
     *
     * @param User $actor
     * @param User $target
     * @param int|string $serviceId
     * @param string|null $role
     * @return Response
     */
    public function updateServiceRole(User $actor, User $target, $serviceId, ?string $role): Response
    {
        // Admin is a global role, whatever service is passed along.
        if ($role === 'Admin') {
            return $this->updateGlobalRole($actor, $target, $role);
        }
        if (!$this->ownService($actor, $serviceId)) {
            return Response::deny();
        }
        if ($this->isSelf($actor, $target)) {
            return Response::denyWithStatus(400, "You can't alter yourself!");
        }
        // Admins have access to all services, they have no service roles.
        if ($this->administer($target)) {
            return Response::denyWithStatus(400, "You can't alter an Admin!");
        }

        return Response::allow();
    }

    /**
     * Invite a (new or existing) user by email with a role
     *
     * @param User $actor
     * @param string $email
     * @param string $role
     * @param array $serviceIds
     * @return Response
     */
    public function invite(User $actor, string $email, string $role, array $serviceIds): Response
    {
        $target = User::where('email', $email)->first();

        if ($target && $this->isSelf($actor, $target)) {
            return Response::denyWithStatus(401, "You can't alter yourself!");
        }
        // Inviting an existing Admin with another role revokes the Admin role.
        $targetIsAdmin = $target && $this->administer($target);
        if (($role === 'Admin' || $targetIsAdmin) && !$this->manageAdmins($actor)) {
            return Response::deny('Only the super admin can manage admins.');
        }
        foreach ($serviceIds as $serviceId) {
            if (!$this->ownService($actor, $serviceId)) {
                return Response::denyWithStatus(401, 'Unauthorized');
            }
        }

        return Response::allow();
    }

    /**
     * The actions the actor may take on the target, as flags for the UI
     *
     * @param User $actor
     * @param User $target
     * @return array
     */
    public function permissionsFor(User $actor, User $target): array
    {
        return [
            'updateGlobalRole' => $this->updateGlobalRole($actor, $target, null)->allowed(),
            'assignAdmin' => $this->updateGlobalRole($actor, $target, 'Admin')->allowed(),
            'delete' => $this->delete($actor, $target)->allowed(),
        ];
    }

    /**
     * @param User $actor
     * @param User $target
     * @return bool
     */
    private function isSelf(User $actor, User $target): bool
    {
        return $actor->id === $target->id;
    }
}
