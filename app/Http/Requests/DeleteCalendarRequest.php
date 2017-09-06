<?php

namespace App\Http\Requests;

use App\Repositories\UserRepository;
use App\Models\Calendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class DeleteCalendarRequest extends FormRequest
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
        $calendar = Calendar::with('openinghours.channel.service')->find($request->calendar);

        if (empty($calendar) || empty($calendar->openinghours->channel->service)) {
            return false;
        }

        return $this->user()->hasRole('Admin')
        || $users->hasRoleInService($this->user()->id, $calendar->openinghours->channel->service->id, 'Owner')
        || $users->hasRoleInService($this->user()->id, $calendar->openinghours->channel->service->id, 'Member');
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
