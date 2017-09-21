<?php

namespace App\Http\Requests;

use App\Formatters\Openinghours as OpeninghoursFormatter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Validator;

/**
 * Vallidation of QueryRequest
 */
class GetQueryRequest extends FormRequest
{

    const TYPE_MAPPER = [
        'now',
        'day',
        'week',
        'fullWeek',
    ];

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
            'serviceUri' => 'required|exists:services,uri',
            'q'          => 'required',
            'date'       => ['regex:/^(0?[1-9]|[12][0-9]|3[01])[\/\-](0?[1-9]|1[012])[\/\-]\d{4}$/'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Validate and sanitize all inputs and combinations.
     * - check q of correct format
     * - check for q day and fullweek a date is given
     * - check if format is conform with OpeninghoursFormatter::OUTPUT_MAPPER
     * - check if given serivce has children
     * - check if (when) given channel is child of given service 
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            /**
             * If q is set (so it won't interact with the required rule),
             * it must be found in the self::TYPE_MAPPER.
             */
            if ($this->input('q') && !in_array($this->input('q'), self::TYPE_MAPPER)) {
                $validator->errors()->add('q', 'The selected parameter q is invalid.');
            }
            /**
             * Date parameter is required when using type day and type fullWeek.
             */
            if ($this->input('q') === self::TYPE_MAPPER[1] && !$this->input('date') ||
                $this->input('q') === self::TYPE_MAPPER[3] && !$this->input('date')) {
                $validator->errors()->add('date', 'The selected parameter date is invalid.');
            }
            /**
             * If format is set, it must be found in the keys of OpeninghoursFormatter::OUTPUT_MAPPER.
             */
            if ($this->input('format') && !in_array($this->input('format'), array_keys(OpeninghoursFormatter::OUTPUT_MAPPER))) {
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
     * Get the failed validation json response for the request.
     * 
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
