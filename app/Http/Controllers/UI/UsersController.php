<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->middleware('admin')->only(['index', 'show', 'destroy']);
        $this->middleware('isOwner')->only(['getFromService', 'invite']);
        $this->userRepository = $userRepository;
        $this->userService = app('UserService');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = $this->userRepository->getAll();

        return response()->json($users);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->userRepository->getById($id));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteUserRequest $request
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if (\Auth::user()->id === $user->id) {
            throw new AuthenticationException("You can't delete yourself!!!");
        }
        $user->delete();

        return response()->json($this->userRepository->getAll());
    }

    /**
     * @todo checkout $service->usersWithRole()
     *
     * @param Service $service
     */
    public function getFromService(Service $service)
    {
        return app('UserRepository')->getAllInService($service->id);
    }

    /**
     * Invite a user to the platform
     *
     * @param Request $request
     * @return User
     */
    public function invite(Request $request)
    {
        $this->validate($request, [
            'email' => 'email|required',
            'role' => 'exists:roles,name|required',
            'service_id' => 'exists:services,id|required_unless:role,Admin',
        ]);

        if (\Auth::user()->email === $request->input('email')) {
            throw new AuthenticationException("You can't alter yourself!!!");
        }

        $role = Role::where('name', $request->input('role'))->first();
        $service = null;
        if ($request->input('service_id')) {
            $service = Service::find($request->input('service_id'));
        }

        $user = $this->userService->setRoleToUser($request->input('email'), $role, $service);
        $user->roles = app('UserRepository')->getAllRolesForUser($user->id);

        return $user;
    }
}
