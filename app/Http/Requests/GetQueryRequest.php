<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Vallidation of QueryRequest
 */
class GetQueryRequest extends FormRequest
{
    /**
     * Is current user authorized to make this request.
     *
     * Set on default true as request is part of OPEN API.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * - check if serice uri given AND is found on service in DB
     * - check q is given
     * - check correct format when date is given
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service' => 'exists:services',
            'channel' => 'exists:channels',
            'date' => 'date',
            'from' => 'date',
            'until' => 'date|after_or_equal:from',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Validate and sanitize all inputs and combinations.
     * - chech if required parameters are given for the correct types
     * - check if requested serivce has children
     * - check if (when) requested channel is child of requested service
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // Pop last argument of path to validate given parameters
            $arguments = explode('/', self::path());
            $lastArgument = array_pop($arguments);

            switch ($lastArgument) {
                case 'open-now':
                    // no parameters are relevant
                    break;
                case 'openinghours':
                    if (!$this->input('from')) {
                        $validator->errors()->add('from', "The 'from' argument is required.");
                    }
                    if (!$this->input('until')) {
                        $validator->errors()->add('until', "The 'until' argument is required.");
                    }
                    if (strtotime($this->input('from')) && strtotime($this->input('until'))) {
                        $from = new Carbon($this->input('from'));
                        $diff = $from->diffInDays(new Carbon($this->input('until')));
                        if ($diff > 366) {
                            $validator->errors()->add(
                                'until',
                                "The difference between from and till may only be max 366 days."
                            );
                        }
                    }
                    break;
                default: // everything else are periods
                    if (!$this->input('date')) {
                        $validator->errors()->add('date', "The 'date' argument is required.");
                    }
            }

            // model binding gives correct App\Models\Service
            $service = $this->route('service');

            if ($service && !$service->channels()->count()) {
                $validator->errors()->add('Service', "The selected service '" . $service->label .
                    "' is not available yet.");
            }

            if ($service && $this->route('channel')) {
                // model binding gives correct App\Models\Channel
                $channelCollection = $service->channels->filter(function ($item) {
                    return $item->id == $this->route('channel')->id;
                });

                if ($channelCollection->isEmpty()) {
                    $validator->errors()->add('Channel', "The selected service '" . $service->label .
                        "' does not contain a channel with the identifier " . $this->route('channel')->id);
                }
            }

            if($service->draft){
                $exception = new ModelNotFoundException();
                $exception->setModel(Service::class);
                throw $exception;
            }
        });
    }
}
