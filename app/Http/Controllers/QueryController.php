<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetQueryRequest;
use App\Models\Service;
use Carbon\Carbon;

/**
 * Controller for query request
 */
class QueryController extends Controller
{
    /**
     * Action for query
     *
     * Check the parameters and load models.
     * Compute data by OpeninghoursService according to param q
     * Send data to formatter by given param format
     * Return formated data as output
     *
     * @param  App\Http\Requests\GetQueryRequest $request
     * @return \Illuminate\Http\Response
     */
    public function query(GetQueryRequest $request)
    {
        if ($request->input('lang')) {
            \App::setLocale($request->input('lang'));
        }
        // set service uri as service unique key
        $serviceModel = Service::where(['uri' => $request->input('serviceUri')])->first();
        $openinghoursService = app('OpeninghoursService');
        $openinghoursService->setServiceModel($serviceModel);

        // set channel if available
        $channelModel = null;
        if ($request->input('channel')) {
            $channelModel = $serviceModel->channels()->where('label', '=', $request->input('channel'))->first();
            $openinghoursService->setChannelModel($channelModel);
        }

        $date = null;
        if ($request->input('date')) {
            $date = Carbon::createFromFormat('d-m-Y', $request->input('date'));
        }
        /** @todo make these sepperate actions on controller */
        try {
            switch ($request->input('q')) {
                case 'now':
                    $openinghoursService->isOpenNow();
                    break;
                case 'day':
                    $openinghoursService->isOpenOnDay($date);
                    break;
                case 'week':
                    $openinghoursService->isOpenForNextSevenDays();
                    break;
                case 'fullWeek':
                    $openinghoursService->isOpenForFullWeek($date);
                    break;
            }
        } catch (\Exception $ex) {
            \Log::error($ex->getMessage());
            \Log::error($ex->getTraceAsString());

            return response()->json(['message' => $ex->getMessage()], 400);
        }

        /** return rendered output **/
        // output format with json as default
        $format = $request->input('format') ?: 'json';
        // special for parent obj in json-ld
        $formatter = app('OpeninghoursFormatter');
        $formatter->serviceUri = $request->input('serviceUri');
        // return rendered data
        $output = $formatter->render($format, $openinghoursService->getData());

        return response()->make($output);
    }
}
