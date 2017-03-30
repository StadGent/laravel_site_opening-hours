<?php

namespace App\Http\Controllers;

use App\Formatters\FormatsOpeninghours;
use Carbon\Carbon;
use Illuminate\Http\Request;

date_default_timezone_set('Europe/Brussels');

class QueryController extends Controller
{
    use FormatsOpeninghours;

    /**
     * Handle an openinghours query
     *
     * @param  Request  $request
     * @return Response
     */
    public function query(Request $request)
    {
        $type = $request->input('q');

        switch ($type) {
            case 'week':
                $data = $this->renderWeekSchedule($request);
                break;
            case 'now':
                $data = $this->isOpenNow($request);
                break;
            case 'day':
                try {
                    $day = new Carbon($request->input('date'));

                    $data = $this->isOpenOnDay($day, $request);
                } catch (\Exception $ex) {
                    \Log::error($ex->getMessage());
                    return response()->json(['message' => 'Something went wrong, are you sure the date is in the expected YYYY-mm-dd format?'], 400);
                }
                break;
            default:
                abort(400, 'The endpoint did not find a handler for your query.');
                break;
        }

        // Check if the format paramater is passed and supported
        $format = $request->input('format');

        // The default format is JSON
        switch ($format) {
            case 'html':
                $data = $this->makeHtmlForSchedule($data);

                return response()->make($data);
                break;
            default:
                return response()->json($data);
                break;
        }
    }

    /**
     * Get the openinghours for a specific day
     *
     * @param  Carbon  $day
     * @param  Request $request
     * @return array
     */
    private function isOpenOnDay($day, $request)
    {
        $services = app('ServicesRepository');
        $openinghoursRepo = app('OpeninghoursRepository');

        // Get the service URI for which we need to compute the week schedule
        $serviceUri = $request->input('serviceUri');
        $channel = $request->input('channel');

        // Get the service
        $service = $services->where('uri', $serviceUri)->first();

        if (empty($service)) {
            return response()->json(['message' => 'The service was not found.'], 404);
        }

        $channels = [];

        // If no channel is passed, return all channels
        if (! empty($channel)) {
            $channels[] = $channel;
        } else {
            $channelObjects = $service->channels->toArray();

            foreach ($channelObjects as $object) {
                $channels[] = $object['label'];
            }
        }

        if (empty($channels)) {
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $result = [];

        foreach ($channels as $channel) {
            $status = 'Gesloten';

            // Get the openinghours for the channel
            $openinghours = $openinghoursRepo->getAllForServiceAndChannel($serviceUri, $channel);

            // Get the openinghours that is active now
            $relevantOpeninghours = '';

            foreach ($openinghours as $openinghoursInstance) {
                if ($day->between(
                    (new Carbon($openinghoursInstance->start_date)),
                    (new Carbon($openinghoursInstance->end_date))
                )) {
                    $relevantOpeninghours = $openinghoursInstance;
                    break;
                }
            }

            if (! empty($openinghours)) {
                // Check if any calendar has an event that falls within the timeframe
                $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                    return $calendar->priority;
                });

                $status = 'Gesloten';

                // Iterate all calendars for the day of the week
                foreach ($calendars as $calendar) {
                    $ical = $this->createIcalFromCalendar($calendar);

                    $dayInfo = $this->extractDayInfo($ical, $day->toDateString(), $day->toDateString());

                    if (! empty($dayInfo)) {
                        $status = $calendar->closinghours ? 'Gesloten' : $dayInfo;

                        break;
                    }
                }
            }

            $result[$channel] = $status;
        }

        return $result;
    }

    /**
     * Calculate if a service is open now
     *
     * @param  Request $request
     * @return array
     */
    private function isOpenNow($request)
    {
        $services = app('ServicesRepository');
        $openinghoursRepo = app('OpeninghoursRepository');

        // Get the service URI for which we need to compute the week schedule
        $serviceUri = $request->input('serviceUri');
        $channel = $request->input('channel');

        // Get the service
        $service = $services->where('uri', $serviceUri)->first();

        if (empty($service)) {
            abort(404, 'The service was not found.');
        }

        $channels = [];

        // If no channel is passed, return all channels
        if (! empty($channel)) {
            $channels[] = $channel;
        } else {
            $channelObjects = $service->channels->toArray();

            foreach ($channelObjects as $object) {
                $channels[] = $object['label'];
            }
        }

        if (empty($channels)) {
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $result = [];

        $now = Carbon::now();

        foreach ($channels as $channel) {
            $status = 'Gesloten';

            // Get the openinghours for the channel
            $openinghours = $openinghoursRepo->getAllForServiceAndChannel($serviceUri, $channel);

            // Get the openinghours that is active now
            $relevantOpeninghours = '';

            foreach ($openinghours as $openinghoursInstance) {
                if ($now->between(
                    (new Carbon($openinghoursInstance->start_date)),
                    (new Carbon($openinghoursInstance->end_date))
                )) {
                    $relevantOpeninghours = $openinghoursInstance;
                    break;
                }
            }

            if (! empty($relevantOpeninghours)) {
                // Check if any calendar has an event that falls within the timeframe
                $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                    return $calendar->priority;
                });

                // Iterate all calendars for the day of the week
                foreach ($calendars as $calendar) {
                    $ical = $this->createIcalFromCalendar($calendar);

                    if ($this->hasEventForRange($ical, $now->toIso8601String(), $now->toIso8601String())) {
                        $status = $calendar->closinghours == 0 ? 'Open' : 'Gesloten';

                        continue;
                    }
                }
            }

            $result[$channel] = $status;
        }

        return $result;
    }

    /**
     * Check if the ICal object has events in the given range
     *
     * @param  ICal    $ical
     * @param  string  $start
     * @param  string  $end
     * @return boolean
     */
    private function hasEventForRange($ical, $start, $end)
    {
        return ! empty($ical->eventsFromRange($start, $end));
    }

    /**
     * Compute a week schedule for a service
     *
     * @param  Request $request
     * @return array
     */
    private function renderWeekSchedule($request)
    {
        $services = app('ServicesRepository');

        // Get the service URI for which we need to compute the week schedule
        $serviceUri = $request->input('serviceUri');
        $channel = $request->input('channel');

        // Get the service
        $service = $services->where('uri', $serviceUri)->first();

        if (empty($service)) {
            return response()->json(['message' => 'The service was not found.'], 404);
        }

        return $this->formatWeek($service['id'], 'array', $channel);
    }
}
