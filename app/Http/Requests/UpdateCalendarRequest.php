<?php

namespace App\Http\Requests;

use App\Models\Calendar;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class UpdateCalendarRequest extends FormRequest
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
        // Get the service of the calendar
        $calendar = Calendar::with('openinghours.channel.service')->find($request->calendar);

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
            'openinghours_id' => 'required|numeric',
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
