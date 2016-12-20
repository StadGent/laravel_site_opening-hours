<?php

namespace App\Http\Requests;

use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(UserRepository $users, Request $request)
    {
        return $this->user()->hasRole('Admin')
        || $users->hasRoleInService($this->user()->id, $request->service_id, 'Owner')
        || $users->hasRoleInService($this->user()->id, $request->service_id, 'Member');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'label' => 'required|min:1'
        ];
    }

    public function messages()
    {
        return $messages = [
            'required' => 'Het veld is verplicht in te vullen.'
        ];
    }
}
