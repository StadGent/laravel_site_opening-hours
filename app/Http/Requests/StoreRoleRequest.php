<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

/**
 * Add a new role to a user, for a certain service
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @param UserRepository $users
     * @param Request $request
     * @return bool
     */
    public function authorize(UserRepository $users, Request $request)
    {
        // A user may delete a role for a user in a service if:
        // the user is a super admin or is an owner of the service
        return $this->user()->hasRole('Admin') || $users->hasRoleInService($this->user()->id, $request->service_id, 'Owner');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'exists:users,id|required|numeric',
            'service_id' => 'exists:services,id|required|numeric',
            'role' => 'exists:roles,name|required',
        ];
    }

    /**
     * Get the messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'Het veld is verplicht in te vullen.',
            'numeric' => 'Het veld moet een numerieke waarde krijgen.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Check if given user_id is not of the current auth user
     * Check if given user_id is not of an Admin user
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $user = User::find($this->input('user_id'));
            if ($this->user()->id == $user->id) {
                $validator->errors()->add('user_id', "You can't alter yourself!");
            }
            if ($user->hasRole('Admin')) {
                $validator->errors()->add('user_id', "You can't alter an Admin!");
            }
        });
    }
}
