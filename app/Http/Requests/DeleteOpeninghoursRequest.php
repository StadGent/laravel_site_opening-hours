<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteOpeninghoursRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // A user may delete a role for a user in a service if:
        // the user is a super admin or is an owner of the service
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
            //
        ];
    }
}
