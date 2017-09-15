<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class GetQueryRequest extends FormRequest
{

    const TYPE_MAPPER = [
        'now',
        'day',
        'week',
        'fullWeek',
    ];

    const FORMAT_MAPPER = [
        'html',
        'text',
        'json-ld',
        'json',

    ];
    /**
     * Determine if the user is authorized to make this request.
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
     * @return array
     */
    public function rules()
    {
        return [
            'serviceUri' => 'required|exists:services,uri',
            'q'          => 'required',
            'date'       => ['regex:/^(0?[1-9]|[12][0-9]|3[01])[\/\-](0?[1-9]|1[012])[\/\-]\d{4}$/'],
        ];
    }

    /**
     * Configure the validator instance.
     * valliddate and sanitize all inputs and combinations
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            /**
             * if q is set (so it won't interact with the required rule)
             * it must be found in the TYPE_MAPPER
             */
            if ($this->input('q') && !in_array($this->input('q'), self::TYPE_MAPPER)) {
                $validator->errors()->add('q', 'The selected parameter q is invalid.');
            }
            /**
             * date parameter is required when using type day and type fullWeek
             */
            if ($this->input('q') === self::TYPE_MAPPER[1] && !$this->input('date') ||
                $this->input('q') === self::TYPE_MAPPER[3] && !$this->input('date')) {
                $validator->errors()->add('date', 'The selected parameter date is invalid.');
            }

            if ($this->input('format') && !in_array($this->input('format'), self::FORMAT_MAPPER)) {
                $validator->errors()->add('format', 'The selected parameter format is invalid.');
            }

            // no fear for injection as the rules() vallidated this input as existing value in db
            $service = \App\Models\Service::where(['uri' => $this->input('serviceUri')])->first();
            if ($service && !$service->channels()->count()) {
                $validator->errors()->add('serviceUri', "The selected service uri is not available yet.");
            }
            if ($service && $this->input('channel')) {
                // no fear for injection as the rules() vallidated this input as existing value in db
                $channel = $service->channels()->where('label', '=', $this->input('channel'))->get();
                if (!$channel) {
                    $validator->errors()->add('channel', 'The selected service does not contain the selected channel.');
                }
            }

        });
    }

    /**
     * overwrite response so only json output will be given and not the http redirect
     * for expectsJson() the headers should correctly be set
     * this function catches all the times this is not correctly done
     *
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        return new JsonResponse($errors, 400);
    }
}
