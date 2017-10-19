<?php

namespace App\Http\Controllers\UI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteUserRequest;
use App\Models\Role;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

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
    public function index(Request $request)
    {
        $users = $this->userRepository->getAll();

        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     * @todo someone pls check or this has any functionality
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return response()->json(['id' => 1]);
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
        $user = $this->userRepository->where('email', $request->input('email'))->first();

        if (empty($user)) {
            $input = $request->input();
            $input['password'] = '';
            $input['token'] = str_random(32);

            $userId = $this->usersuserRepository->store($input);

            $user = $this->userRepository->getById($userId);

            // Attach the role of application user to the new user
            $appUserRole = Role::where('name', 'AppUser')->first();

            $user->attachRole($appUserRole);

            // Send a confirmation email
            $mailer = app()->make('App\Mailers\SendGridMailer');
            $mailer->sendEmailConfirmationTo($user['email'], $user['token']);
        }

        if (!empty($user)) {
            return response()->json($user);
        }

        return response()->json(['message' => 'Something went wrong while storing the user, check the logs.'], 400);
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
     * Show the form for editing the specified resource.
     *
     * @todo someone pls check or this has any functionality
     * @param  int $id
     * @return ???
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @todo someone pls check or this has any functionality
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return ???
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteUserRequest $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteUserRequest $request, $id)
    {
        $success = app(UserRepository::class)->delete($id);

        if ($success !== false) {
            $users = $this->userRepository->getAll();

            return response()->json($users);
        }

        return response()->json('Something went wrong while deleting the user, check the logs for more info.', 400);
    }

    /**
     * @param $id
     */
    public function getUsersByService($id)
    {
        return app('UserRepository')->getAllInService($id);
    }
}
