<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\DeleteRoleRequest;

/**
 * RolesController takes care of CRUD'ing of roles
 * amongst users
 */
class RolesController extends Controller
{
    public function __construct(UserRepository $users)
    {
        $this->middleware('auth');

        $this->users = $users;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRoleRequest $request)
    {
        $input = $request->input();

        $success = $this->users->linkToService($input['user_id'], $input['service_id'], $input['role']);

        if ($success) {
            return response()->json(['message' => 'De rol werd toegevoegd.']);
        }

        return response()->json(['message' => 'Er is iets misgegaan tijdens het toevoegen van de rol.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteRoleRequest $request)
    {
        $input = $request->input();

        $success = $this->users->removeRoleInService($input['user_id'], $input['service_id']);

        if ($success) {
            return response()->json(['message' => 'De gebruiker werd bijgewerkt.']);
        }

        return response()->json(['message' => 'Er is iets foutgegaan bij het bewerken van een gebruiker.'], 400);
    }
}
