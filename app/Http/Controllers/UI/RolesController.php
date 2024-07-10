<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * RolesController takes care of CRUD'ing of roles
 * amongst users
 */
class RolesController extends Controller
{
    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->middleware('isOwner');
        $this->users = $users;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRoleRequest $request)
    {
        $user = User::find($request->input('user_id'));
        $role = Role::where('name', $request->input('role'))->first();

        if ($request->input('role') === 'Editor') {
            $user->roles()->sync([$role->id]);
            return response()->json(['role' => $request->input('role')]);
        }

        if ($request->input('role') === null) {
            $user->roles()->sync([]);
            return response()->json(['role' => $request->input('role')]);
        }

        $services = collect(Service::find($request->input('service_id')));
        $services = Service::whereIn('id', [$request->input('service_id')])->get();

        $user = app('UserService')->setRolesToUser($user->email, $role, $services);
        $assignedRoles = app('UserRepository')->getAllRolesForUser($user->id);

        foreach ($assignedRoles as $seriveRole) {
            if ($seriveRole['service_id'] == $request->input('service_id') &&
                $seriveRole['role'] === $request->input('role')) {
                return response()->json(['role' => $request->input('role')]);
            }
        }

        return response()
            ->json(['message' => 'Er is iets misgegaan tijdens het aanpassen van de rol.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteRoleRequest $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteRoleRequest $request)
    {
        $input = $request->input();
        $success = $this->users
            ->removeRoleInService($input['user_id'], $input['service_id']);

        if (!$success) {
            return response()
                ->json(['message' => 'Er is iets foutgegaan bij het bewerken van een gebruiker.'], 400);
        }

        return response()->json(['message' => 'De gebruiker werd bijgewerkt.']);
    }
}
