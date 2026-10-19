<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
        return response()->json($this->usersWithPermissions());
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
        $this->authorize('delete', $user);
        $user->delete();

        return response()->json($this->usersWithPermissions());
    }

    /**
     * All users, each with what the current user may do with them
     *
     * @return array
     */
    private function usersWithPermissions(): array
    {
        $policy = Gate::getPolicyFor(User::class);
        $results = $this->userRepository->getAll();
        $users = User::findMany(array_column($results, 'id'))->keyBy('id');

        return array_map(function (array $user) use ($policy, $users) {
            $user['can'] = $policy->permissionsFor(\Auth::user(), $users[$user['id']]);
            return $user;
        }, $results);
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

        $this->authorize('invite', [
            User::class,
            $request->input('email'),
            $request->input('role'),
            (array) $request->input('service_id'),
        ]);

        $role = Role::where('name', $request->input('role'))->first();
        $services = new Collection();
        if ($request->has('service_id')) {
            $serviceIds = (array) $request->input('service_id');
            // Get all the services based on the given service ids.
            $services = Service::whereIn('id', $serviceIds)->get();
        }

        $user = $this->userService->setRolesToUser($request->input('email'), $role, $services);
        $user->roles = app('UserRepository')->getAllRolesForUser($user->id);
        $user->can = Gate::getPolicyFor(User::class)->permissionsFor(\Auth::user(), $user);

        return $user;
    }
}
