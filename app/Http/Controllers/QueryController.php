<?php

namespace App\Http\Controllers;

use App\Formatters\OpeninghoursFormatter;
use App\Http\Requests\GetQueryRequest;
use App\Models\Channel;
use App\Models\Service;
use App\Services\OpeninghoursService;
use Carbon\Carbon;

/**
 * Controller for query request
 */
class QueryController extends Controller
{
    /**
     * @var App\Services\OpeninghoursService
     */
    private $OpeninghoursService;

    /**
     * @var App\Formatters\OpeninghoursFormatter
     */
    private $OpeninghoursFormatter;

    /**
     * @param OpeninghoursService $ohService
     * @param OpeninghoursFormatter $ohFormatter
     */
    public function __construct()
    {
        $this->OpeninghoursService = app('OpeninghoursService');
        $this->OpeninghoursFormatter = app('OpeninghoursFormatter');
    }

    /**
     * Collection of Channels with values or is now open or not
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function nowOpenAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $this->OpeninghoursService->isOpenNow($service, $channel, $request->input('testDateTime'));
        // output format with json as default
        $this->OpeninghoursFormatter->setService($service);
        $output = $this->OpeninghoursFormatter->render(
            $request->input('format') ?: 'json',
            $this->OpeninghoursService->getData()
        );

        return response()->make($output);
    }

    /**
     * Collection of openinghours with custom from - till
     *
     * @todo  check the correct output by Accept header
     * @param Request $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function fromTillAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $start = new Carbon($request['from']);
        $end = new Carbon($request['until']);
        $output = $this->generateOutput(
            $start->startOfDay(),
            $end->endOfDay(),
            $service,
            $channel,
            $request->input('format')
        );

        return response()->make($output);
    }

    /**
     * Collection of openinghours for one day
     *
     * @todo  check the correct output by Accept header
     * @param Request $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function dayAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $start = new Carbon($request['date']);
        $end = $start->copy()->endOfDay();
        $output = $this->generateOutput($start, $end, $service, $channel, $request->input('format'));

        return response()->make($output);
    }

    /**
     * Collection of openinghours for one week
     *
     * @todo  check the correct output by Accept header
     * @todo  find week based on given locale
     * @param Request $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function weekAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $start = $date->copy()->startOfWeek();
        $end = $date->copy()->endOfWeek();
        $output = $this->generateOutput($start, $end, $service, $channel, $request->input('format'));

        return response()->make($output);
    }

    /**
     * Collection of openinghours for one month
     *
     * @todo  check the correct output by Accept header
     * @param Request $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function monthAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();
        $output = $this->generateOutput($start, $end, $service, $channel, $request->input('format'));

        return response()->make($output);
    }

    /**
     * Collection of openinghours for one year
     *
     * @todo  check the correct output by Accept header
     * @param Request $request
     * @param Service $service
     * @param Channel $channel
     * @return \Illuminate\Http\Response
     */
    public function yearAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $start = $date->copy()->startOfYear();
        $end = $date->copy()->endOfYear();
        $output = $this->generateOutput($start, $end, $service, $channel, $request->input('format'));

        return response()->make($output);
    }

    /**
     * Get the data from the service
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param Service $service
     * @param Channel $channel
     * @param string $format
     * @return mixed
     */
    private function generateOutput(Carbon $start, Carbon $end, Service $service, Channel $channel, $format)
    {
        $this->OpeninghoursService->collectData($start, $end, $service, $channel);
        $this->OpeninghoursFormatter->setService($service);

        return $this->OpeninghoursFormatter->render(
            $format ?: 'json',
            $this->OpeninghoursService->getData()
        );
    }
}
