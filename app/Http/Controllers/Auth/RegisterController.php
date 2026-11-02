<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | Public self-registration is disabled: users are invited by an admin or
    | service owner. This controller only handles completing an invitation by
    | setting a password through the token sent in the confirmation mail.
    |
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * @param Request $request
     * @param $token
     */
    public function showSetPassword(Request $request, $token)
    {
        $user = User::where('token', $token)->first();

        if (empty($user)) {
            return redirect('/');
        }

        return view('auth.confirm')->with([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * @param Request $request
     * @param $token
     */
    public function completeRegistration(Request $request, $token)
    {
        $this->registrationCompletionValidator($request->all())->validate();

        // Only the secret token from the confirmation mail identifies the user.
        // Never trust user-supplied input such as the e-mail address here.
        $user = User::where('token', $token)->whereNotNull('token')->first();

        if (!empty($user)) {
            $user->password = Hash::make($request->input('password'));
            $user->token = null;
            $user->save();

            Auth::login($user);
        }

        return redirect('/');
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
            'password' => ['required', 'min:8', 'confirmed'],
        ]);
    }
}
