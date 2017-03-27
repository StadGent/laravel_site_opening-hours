<?php

namespace App\Http\Requests;

use App\Models\Openinghours;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class DeleteOpeninghoursRequest extends FormRequest
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
        $openinghours = Openinghours::with('channel.service')->find($request->openinghour);

        if (empty($openinghours) || empty($openinghours->channel->service)) {
            return false;
        }

        return $this->user()->hasRole('Admin')
        || $users->hasRoleInService($this->user()->id, $openinghours->channel->service->id, 'Owner')
        || $users->hasRoleInService($this->user()->id, $openinghours->channel->service->id, 'Member');
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
