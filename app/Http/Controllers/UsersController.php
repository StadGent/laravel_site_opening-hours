<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Repositories\UserRepository;
use App\Http\Requests\DeleteUserRequest;
use Auth;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(UserRepository $users)
    {
        $this->middleware('auth');

        $this->users = $users;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = $this->users->getAll();

        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return response()->json([ 'id' => 1 ]);
    }

    /**
     * Upsert a user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if the user already exists
        $user = $this->users->where('email', $request->input('email'))->first();

        if (empty($user)) {
            $input = $request->input();
            $input['password'] = '';
            $input['token'] = str_random(32);

            $userId = $this->users->store($input);

            $user = $this->users->getById($userId);

            // Attach the role of application user to the new user
            $appUserRole = Role::where('name', 'AppUser')->first();

            $user->attachRole($appUserRole);

            // Send a confirmation email
            $mailer = app()->make('App\Mailers\SendGridMailer');
            $mailer->sendEmailConfirmationTo($user['email'], $user['token']);
        }

        if (! empty($user)) {
            return response()->json($user);
        }

        return response()->json(['message' => 'Something went wrong while storing the user, check the logs.'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->users->getById($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  DeleteUserRequest         $request
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteUserRequest $request, $id)
    {
        $success = app(UserRepository::class)->delete($id);

        if ($success !== false) {
            $users = $this->users->getAll();

            return response()->json($users);
        }

        return response()->json('Something went wrong while deleting the user, check the logs for more info.', 400);
    }
}
