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
    /**
     * @var LocaleService
     */
    private $localeService;

    const CALENDAR_LENGTH_DAY = 'day';
    const CALENDAR_LENGTH_MONTH = 'month';
    const CALENDAR_LENGTH_MULTIPLE_DAYS = 'multiple_days';
    const CALENDAR_LENGTH_OPEN_NOW = 'open_now';

    const SUPPORTED_CALENDAR_LENGTHS = [
        self::CALENDAR_LENGTH_DAY,
        self::CALENDAR_LENGTH_MONTH,
        self::CALENDAR_LENGTH_MULTIPLE_DAYS,
    ];

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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_OPEN_NOW, true);
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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_MULTIPLE_DAYS);
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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_DAY);
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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_MULTIPLE_DAYS);
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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_MONTH);
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

        return $this->getResponse($request, $start, $end, $service, $channel, self::CALENDAR_LENGTH_MULTIPLE_DAYS);
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
        $calendarLength,
        $includeIsOpenNow = false
    ) {
        $this->localeService->setRequest($request);
        $hasOneChannel = isset($channel->id);

        $transformer = new OpeninghoursTransformer();
        $transformer->setStart($start);
        $transformer->setEnd($end);
        $transformer->setService($service);
        $transformer->setLocaleService($this->localeService);
        $transformer->setIncludeIsOpenNow($includeIsOpenNow);
        $transformer->setHasOneChannel($hasOneChannel);
        $transformer->setCalendarLength($calendarLength);

        $channels = isset($channel->id) ? (new Collection())->add($channel) : $service->channels;

        return response()->collection($transformer, $channels);
    }
}
