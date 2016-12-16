<?php

namespace App\Http\Requests;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Add a new role to a user, for a certain service
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
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
            'user_id' => 'required|numeric',
            'service_id' => 'required|numeric',
            'role' => 'required|in:Owner,Member'
        ];
    }
}
