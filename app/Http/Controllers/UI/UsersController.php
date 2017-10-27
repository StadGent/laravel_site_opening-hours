<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Mail\SendRegisterConfirmation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UsersController extends Controller
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
     * Upsert a user
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if the user already exists
        if (!$this->userRepository->where('email', $request->input('email'))->get()->isEmpty()) {
            // find correct duplicate data error code
            return response()->json(['message' => 'This User already exists in the DB.'], 400);
        }

        $input = $request->input();
        $input['password'] = '';
        $input['token'] = str_random(32);

        $userId = $this->userRepository->store($input);
        $user = $this->userRepository->getById($userId);
        if (!$user) {
            return response()->json(['message' => 'Something went wrong while storing the user, check the logs.'], 400);
        }

        Mail::to($user)->send(new SendRegisterConfirmation($user));

        return response()->json($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if (\Auth::user()->id === $user->id) {
            throw new AuthenticationException("You can't delete yourself!!!");
        }

        $success = $user->delete();
        if ($success !== false) {
            $users = $this->userRepository->getAll();

            return response()->json($users);
        }

        return response()->json('Something went wrong while deleting the user, check the logs for more info.', 400);
    }

    /**
     * @todo checkout $service->usersWithRole()
     * @param Service $service
     */
    public function getFromService(Service $service)
    {
        return app('UserRepository')->getAllInService($service->id);
    }

    /**
     * @param Request $request
     */
    public function invite(Request $request)
    {
        $user = User::where('email', $request->input('email'));
        if ($user->isEmpty()) {
            $user = $this->store($request);
        }

        $role = false;
        if ($request->input('role')) {
            $role = Role::where('name', $request->input('role'))->first();
        }
        if ($request->input('role_id')) {
            $role = Role::find($request->input('role_id'));
        }
        if (!isset($role->id)) {
            throw new ValidationException(['message' => ['role' => 'Vallid role is required to invite a user']]);
        }

        $this->userRepository->linkToService($user->id, $request->input('service_id'), $role->name);
        $user->fresh();

        return $user;
    }
}
