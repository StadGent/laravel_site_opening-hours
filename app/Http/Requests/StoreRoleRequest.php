<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Add a new role to a user, for a certain service
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * No role, Editor and Admin are global roles (see RolesController::update),
     * Owner and Member are roles in the given service. See UserPolicy for the
     * rules.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function authorize()
    {
        $target = User::find($this->input('user_id'));
        if (!$target) {
            // Let the validation report the unknown user.
            return true;
        }

        $gate = Gate::forUser($this->user());
        if (in_array($this->input('role'), [null, 'Editor', 'Admin'], true)) {
            return $gate->inspect('updateGlobalRole', [$target, $this->input('role')]);
        }

        return $gate->inspect(
            'updateServiceRole',
            [$target, $this->input('service_id'), $this->input('role')]
        );
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
            'service_id' => 'nullable|exists:services,id|numeric',
            'role' => 'nullable|exists:roles,name',
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
}
