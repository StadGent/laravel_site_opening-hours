<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * See UserPolicy::updateServiceRole() for the rules.
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

        return Gate::forUser($this->user())
            ->inspect('updateServiceRole', [$target, $this->input('service_id'), null]);
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
            'service_id' => 'required|numeric',
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
