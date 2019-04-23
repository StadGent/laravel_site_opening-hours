<?php

namespace App\Http\Requests;

use App\Models\Channel;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class StoreOpeninghoursRequest extends FormRequest
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
        // A user may delete a role for a user in a service if:
        // the user is a super admin or is an owner of the service
        // Get the service of the calendar
        $channel = Channel::with('service')->find($request->channel_id);

        if (empty($channel)
            || empty($channel->service->id)) {
            return false;
        }

        $serviceId = $channel->service->id;

        return $this->user()->hasRole('Admin') || $this->user()->hasRole('Editor')
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
            'channel_id' => 'exists:channels,id|required|numeric',
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

    /**
     * Configure the validator instance.
     *
     * Check if given channel_id is parent of given id
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // model binding gives correct App\Models\Openinghours
            if (!empty($this->route('openinghours'))) {
                $openinghours = $this->route('openinghours');
                if ($this->input('channel_id') != $openinghours->channel->id) {
                    $validator->errors()->add(
                        'channel_id',
                        "The given channel_id attribute is not a parent of the requested Openinghours id"
                    );
                }
            }
        });
    }
}
