<?php

namespace App\Http\Requests;

use App\Models\Channel;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class StoreOpeninghoursRequest extends FormRequest
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
        // Get the service of the calendar
        $channel = Channel::with('service')->find($request->channel_id);

        if (empty($channel) || empty($channel->service->id)) {
            return false;
        }

        $serviceId = $channel->service->id;

        return $this->user()->hasRole('Admin')
        || $users->hasRoleInService($this->user()->id, $serviceId, 'Owner')
        || $users->hasRoleInService($this->user()->id, $serviceId, 'Member');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'label' => 'required',
            'channel_id' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return $messages = [
            'required' => 'Het veld is verplicht in te vullen.',
            'numeric' => 'Het veld moet een numerieke waarde krijgen.'
        ];
    }
}
