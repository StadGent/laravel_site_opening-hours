<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetQueryRequest;
use App\Models\Channel;
use App\Models\Service;
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

    public function __construct()
    {
        $this->OpeninghoursService = app('OpeninghoursService');
        $this->OpeninghoursFormatter = app('OpeninghoursFormatter');
    }

    /**
     * Collection of Channels with values or is now open or not
     *
     * @todo  check the correct output by Accept header
     * @param  GetQueryRequest $request [description]
     * @param  Service         $service [description]
     * @param  Channel         $channel [description]
     * @return \Illuminate\Http\Response
     */
    public function nowOpenAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $this->OpeninghoursService->isOpenNow($service, $channel, $request->input('testDateTime'));
        // output format with json as default
        $output = $this->OpeninghoursFormatter->render('json', $this->OpeninghoursService->getData());

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

        $this->OpeninghoursService->collectData($start->startOfDay(), $end->endOfDay(), $service, $channel);
        $output = $this->OpeninghoursFormatter->render($request->input('format') ?: 'json', $this->OpeninghoursService->getData());

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

        $this->OpeninghoursService->collectData($start, $end, $service, $channel);

        $this->OpeninghoursService->getData();
        $output = $this->OpeninghoursFormatter->render($request->input('format') ?: 'json', $this->OpeninghoursService->getData());

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
        $this->OpeninghoursService->collectData($start, $end, $service, $channel);
        $output = $this->OpeninghoursFormatter->render($request->input('format') ?: 'json', $this->OpeninghoursService->getData());

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

        $this->OpeninghoursService->collectData($start, $end, $service, $channel);
        $output = $this->OpeninghoursFormatter->render($request->input('format') ?: 'json', $this->OpeninghoursService->getData());

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

        $this->OpeninghoursService->collectData($start, $end, $service, $channel);
        $output = $this->OpeninghoursFormatter->render($request->input('format') ?: 'json', $this->OpeninghoursService->getData());

        return response()->make($output);
    }

}
