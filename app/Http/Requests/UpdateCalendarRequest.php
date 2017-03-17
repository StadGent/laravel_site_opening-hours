<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use App\Models\Calendar;

class UpdateCalendarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(UserRepository $users, Request $request)
    {
        // Get the service of the calendar
        $calendar = Calendar::with('openinghours.channel.service')->find($request->id);

        if (empty($calendar) || empty($calendar->openinghours->channel->service->id)) {
            return false;
        }

        $serviceId = $calendar->openinghours->channel->service->id;

        // A user may delete a role for a user in a service if:
        // the user is a super admin or is an owner of the service
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
            'priority' => 'required|numeric',
            'label' => 'required',
            'openinghours_id' => 'required|numeric'
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
