<?php

namespace App\Http\Controllers;

use App\Formatters\Openinghours;
use App\Http\Requests\GetQueryRequest;
use App\Models\Service;
use Carbon\Carbon;

class QueryController extends Controller
{
    /**
     * Handle an openinghours query
     *
     * @todo make the q input sepperate actions on controller
     * @param  GetQueryRequest $request [extends FormRequest]
     * @return [type]                   [description]
     */
    public function query(GetQueryRequest $request, Openinghours $formatter)
    {
        if ($request->input('lang')) {
            \App::setLocale($request->input('lang'));
        }
        // set service uri as service unique key
        $serviceModel        = Service::where(['uri' => $request->input('serviceUri')])->first();
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
            $splitDate = explode('-', $request->input('date'));
            $date      = Carbon::createFromDate($splitDate[2], $splitDate[1], $splitDate[0])->startOfDay();
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
        $formatter->serviceUri = $request->input('serviceUri');
        // return rendered data
        $output = $formatter->render($format, $openinghoursService->getData());
        return response()->make($output);
    }
}
