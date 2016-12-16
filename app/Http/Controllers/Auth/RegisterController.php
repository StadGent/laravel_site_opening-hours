<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        // Attach the role of application user to the new user
        $appUserRole = Role::where('name', 'AppUser')->first();

        $user->attachRole($appUserRole);
        $user->save();

        $mailer = app()->make('App\Mailers\SendGridMailer');
        $mailer->sendEmailConfirmationTo($user->email, $user->token);

        return response()->json(['message' => 'De gebruiker werd succesvol aangemaakt.']);
    }

    public function showSetPassword(Request $request, $token)
    {
        $user = User::where('token', $token)->first();

        if (empty($user)) {
            return redirect('/');
        }

        return view('auth.confirm')->with([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function completeRegistration(Request $request)
    {
        $this->registrationCompletionValidator($request->all())->validate();

        $input = $request->input();

        $user = User::where('email', $input['email'])->first();

        if (! empty($user)) {
            $user->password = bcrypt($input['password']);
            $user->token = null;
            $user->save();

            Auth::login($user);
        }

        return redirect('/');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array                                      $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'min:6|confirmed',
        ]);
    }

    /**
     * Get a validator for a registration completion request.
     *
     * @param  array                                      $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function registrationCompletionValidator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|email',
            'password' => 'min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => @$data['name'],
            'email' => $data['email'],
            'password' => '',
            'token' => str_random(32),
            'verified' => false
        ]);

        return $user;
    }
}
