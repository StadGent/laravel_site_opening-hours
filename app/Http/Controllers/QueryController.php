<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetQueryRequest;
use App\Http\Transformers\OpeninghoursTransformer;
use App\Models\Channel;
use App\Models\Service;
use App\Services\LocaleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

/**
 * Controller for query request
 */
class QueryController extends Controller
{

    private $localeService;

    public function __construct()
    {
        $this->localeService = app(LocaleService::class);
    }

    /**
     * Collection of Channels with values or is now open or not
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function nowOpenAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $start = Carbon::now();
        $end = $start->copy()->addMinute();
        $testDateTime = $request->input('testDateTime');

        if (!is_null($testDateTime)) {
            $start = new Carbon($testDateTime);
        }

        return $this->getResponse($request, $start, $end, $service, $channel, true);
    }

    /**
     * Collection of openinghours with custom from - till
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function fromTillAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $start = new Carbon($request['from']);
        $end = new Carbon($request['until']);

        return $this->getResponse($request, $start, $end, $service, $channel);
    }

    /**
     * Collection of openinghours for one day
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function dayAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $start = new Carbon($request['date']);
        $end = $start->copy()->endOfDay();

        return $this->getResponse($request, $start, $end, $service, $channel);
    }

    /**
     * Collection of openinghours for one week
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function weekAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $this->localeService->setRequest($request);
        $date->setWeekStartsAt($this->localeService->getWeekStartDay());
        $date->setWeekEndsAt($this->localeService->getWeekEndDay());

        $start = $date->copy()->startOfWeek();
        $end = $date->copy()->endOfWeek();

        return $this->getResponse($request, $start, $end, $service, $channel);
    }

    /**
     * Collection of openinghours for one month
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function monthAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        return $this->getResponse($request, $start, $end, $service, $channel);
    }

    /**
     * Collection of openinghours for one year
     *
     * @param GetQueryRequest $request
     * @param Service $service
     * @param Channel $channel
     * @return Response
     */
    public function yearAction(GetQueryRequest $request, Service $service, Channel $channel)
    {
        $date = new Carbon($request['date']);
        $start = $date->copy()->startOfYear();
        $end = $date->copy()->endOfYear();

        return $this->getResponse($request, $start, $end, $service, $channel);
    }

    /**
     * Generate a response based on predefined parameters
     *
     * @param GetQueryRequest $request
     * @param Carbon $start
     * @param Carbon $end
     * @param Service $service
     * @param Channel $channel
     * @param bool $includeIsOpenNow
     * @return Response
     */
    public function getResponse(
        GetQueryRequest $request,
        Carbon $start,
        Carbon $end,
        Service $service,
        Channel $channel,
        $includeIsOpenNow = false
    ) {
        $this->localeService->setRequest($request);

        $transformer = new OpeninghoursTransformer();
        $transformer->setStart($start);
        $transformer->setEnd($end);
        $transformer->setService($service);
        $transformer->setLocaleService($this->localeService);
        $transformer->setIncludeIsOpenNow($includeIsOpenNow);

        $channels = isset($channel->id) ? (new Collection())->add($channel) : $service->channels;

        return response()->collection($transformer, $channels);
    }

}
