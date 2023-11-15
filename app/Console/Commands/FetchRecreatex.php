<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Event;
use App\Models\Openinghours;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class FetchRecreatex
 * @package App\Console\Commands
 */
class FetchRecreatex extends BaseCommand
{
    /**
     * @var string
     */
    protected $signature = 'openinghours:fetch-recreatex';

    /**
     * @var string
     */
    protected $description = 'Fetch RECREATEX openinghours data';

    /**
     * @var \SoapClient
     */
    private $soapClient;

    /**
     * @var string
     */
    private $shopId;

    /**
     * @var Carbon
     */
    private $calendarStartYear;

    /**
     * @var Carbon
     */
    private $calendarEndYear;

    /**
     * @var string
     */
    private $channelName;

    /**
     * @var string[]
     */
    private $sportsUuids;

    /**
     * @var string
     */
    private $calendarName;

    /**
     * @var App\Models\Service
     */
    private $activeServiceRecord;

    const WEEKDAYS = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->shopId = env('SHOP_ID');
        $this->channelName = env('CHANNEL_NAME');
        $this->calendarName = env('CALENDAR_NAME');
        $this->calendarStartYear = Carbon::now()->year;
        $this->calendarEndYear = Carbon::now()->addYear(3)->year;
        $this->sportsUuids = explode(',', env('SPORTS_UUIDS'));
    }

    /**
     * Execute the console command.
     *
     * Get all recreatex services where recreatex is the source
     * oredered by last update to not always start with the same when last loop was broken
     * Check if the identifier is present
     * Loop over the recreatex services that pass the filter
     *
     */
    public function handle()
    {
        $this->comment('Init handle FetchRecreatex');
        Service::where(['source' => 'recreatex'])->orderBy('updated_at', 'ASC')->get()
            ->filter(function (Service $service, $key) {
                return !empty($service->identifier);
            })
            ->each(function (Service $service, $key) {
                $this->activeServiceRecord = $service;
                if ($this->handleService()) {
                    $this->activeServiceRecord->updated_at = Carbon::now();
                    $this->activeServiceRecord->save();
                }
            });

        return 0;
    }

    /**
     * Handle a recreatex service
     *
     * save updated_at of service when done to trigger the order
     *
     * @return bool
     */
    private function handleService()
    {
        $this->comment('Handling service "' . $this->activeServiceRecord->label . '"');

        $channel = $this->getOrCreateChannel();

        // Loop over the predefined years and handle it in a different function
        $succes = true;
        for ($year = $this->calendarStartYear; $year <= $this->calendarEndYear; $year++) {
            $succes = $succes && $this->handleCalendarYear($channel, $year);
            $this->handleReservations($year);
        }
        if (!$succes) {
            $this->error('Not able to sync recreatex for "' . $this->activeServiceRecord->label . '"');
        }

        return $succes;
    }

    private function handleReservations($year)
    {
        $success = true;
        $processedChannels = [
            $this->channelName,
        ];
        foreach ($this->sportsUuids as $uuid) {
            $reservations = $this->getReservations($uuid, $year);
            if (!$reservations) {
                continue;
            }
            $dailyEventList = [];
            foreach ($reservations as $reservation) {
                $channelName = $reservation['ReservationActivity']['Description'];
                $date = Carbon::createFromFormat('Y-m-d\TH:i:s', $reservation['StartDateTime'])
                    ->setTime(0, 0, 0)->format('Y-m-d\TH:i:s');
                $dailyEventList[$date][] = [
                  'Date' => $date,
                  'From1' => $reservation['StartDateTime'],
                  'To1' => $reservation['EndDateTime'],
                  'From2' => null,
                  'To2' => null,
                  'start' => Carbon::createFromFormat('Y-m-d\TH:i:s', $reservation['StartDateTime'])->getTimestamp(),
                  'end' => Carbon::createFromFormat('Y-m-d\TH:i:s', $reservation['EndDateTime'])->getTimestamp(),
                ];
            }
            $dailyEventList = array_map([$this, 'processCollapsedReservations'], $dailyEventList);
            $eventList = [];
            foreach ($dailyEventList as $dayEvents) {
                $eventList = array_merge($eventList, $dayEvents);
            }
            $channel = $this->getOrCreateChannel($channelName);
            $channel->type()->associate(\App\Models\Type::where('name', 'Algemeen')->first());
            $channel->weight = env('SPORTS_RESERVATION_CHANNEL_WEIGHT', 1);
            $channel->save();
            $openinghours = $this->getOrCreateOpeninghours($channel, $year);
            $calendar = $this->getOrCreateCalendar($openinghours);
            $calendar->published = true;
            $calendar->save();

            $success = $success && $this->fillCalendar($calendar, $year, $eventList);
            $processedChannels[] = $channelName;
        }
        $channels = Channel::where('service_id', $this->activeServiceRecord->id)
            ->whereNotIn('label', $processedChannels)
            ->get();
        foreach ($channels as $channelToCheck) {
            $this->clearChannelOpeningHour($channelToCheck, $year);
        }
        return $success;
    }

    protected function getReservations($uuid, $year)
    {
        $reservations = [];
        foreach (range(1, 12) as $month) {
            $monthReservations = $this->getReservationsForMonth($uuid, $year, $month);
            if ($monthReservations) {
                $reservations = array_merge($reservations, $monthReservations);
            }
        }

        return $reservations;
    }

    protected function getReservationsForMonth($uuid, $year, $month) {
        $start = Carbon::createFromDate($year, $month)->setTimezone('Europe/Brussels')->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $parameters = [
            'Context' => [
                'ShopId' => $this->shopId,
            ],
            'ReservationSearchCriteria' => [
                'InfrastructureId' => $this->activeServiceRecord->identifier,
                'FromDateTime' => $start->format('Y-m-d\TH:i:s.uP'),
                'ToDateTime' => $end->format('Y-m-d\TH:i:s.uP'),
                'ReservationActivityId' => $uuid,
                'Includes' => [
                    'SingleReservations' => true,
                    'ReservationsInList' => true,
                    'SerieReservations' => true,
                    'PlaceInfo' => true,
                    'InfrastructureInfo' => true,
                    'ReservationActivityInfo' => true,
                    'ReservedPlaces' => true,
                ],
                'Paging' => [
                    'PageSize' => 999999,
                    'PageIndex' => 0,
                ]
            ],
        ];

        try {
            $response = $this->getClient()->FindReservations($parameters);
            $transformedData = json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            $this->error('A problem in collecting external data from Recreatex for ' . $this->activeServiceRecord->label . ' with year ' .
                $year . ' and month ' . $month . ': ' . $e->getMessage());

            return false;
        }
        if (!$transformedData['Reservations']) {
            return false;
        }
        $reservations = array_filter(Arr::get($transformedData, 'Reservations.Reservation', 0));
        // SOAP inconsistency: if there's only one result, it doesn't wrap it in an array.
        if ($reservations && isset($reservations['Id'])) {
          $reservations = [$reservations];
        }
        return $reservations;
    }

    private function processCollapsedReservations($eventList)
    {
        $openings = [];
        usort($eventList, function ($a, $b) {
            return $a['start'] === $b['start']
                ? $a['end'] - $b['end']
                : $a['start'] - $b['start'];
        });
        $openings[] = array_shift($eventList);
        foreach ($eventList as $event) {
            $previous = end($openings);
            $key = key($openings);
            // This reservation does not overlap with the previous one, so add
            // it as a new one.
            if (!($previous['start'] <= $event['end'] && $previous['end'] >= $event['start'])) {
                $openings[] = $event;
                continue;
            }
            // They overlap. Adjust the dates of the previous event.
            if ($previous['start'] > $event['start']) {
                $openings[$key]['start'] = $event['start'];
                $openings[$key]['From1'] = $event['From1'];
            }
            if ($previous['end'] < $event['end']) {
                $openings[$key]['end'] = $event['end'];
                $openings[$key]['To1'] = $event['To1'];
            }
        }
        return $openings;
    }

    /**
     * Handle a calendar year for a recreatex service
     *
     * @param Channel $channel
     * @param $year
     */
    private function handleCalendarYear(Channel $channel, $year)
    {
        // Get the opening hours list from the recreatex soap service
        $eventList = $this->getOpeninghoursList($year);
        if ($eventList === false) {
            $this->error('Could not collect eventList for "' . $this->activeServiceRecord->label . '" in year ' . $year);

            return false;
        }

        // If the list is empty all openinghours of the existing channels are removed
        if (empty($eventList)) {
            $this->clearChannelOpeningHour($channel, $year);

            return true;
        }

        $openinghours = $this->getOrCreateOpeninghours($channel, $year);
        $calendar = $this->getOrCreateCalendar($openinghours);

        return $this->fillCalendar($calendar, $year, $eventList);
    }

    /**
     * Fill the calendar with events based on a list with dates and hours
     *
     * @param Calendar $calendar
     * @param int $year
     * @param array $list
     */
    private function fillCalendar(Calendar $calendar, $year, $eventList)
    {
        $this->sortEventList($eventList);

        $sequences = $this->getSequences($eventList, $year);

        // If no changes where made the calendar doesn't
        if (!$this->isCalendarUpdated($calendar, $sequences)) {
            return true;
        }

        $this->info('New data in calendar for "' . $this->activeServiceRecord->label . '" in year ' . $year);
        $this->clearCalendar($calendar);

        // Store the sequences as rules in the database
        $succes = true;
        foreach ($sequences as $index => $sequence) {
            if (!($succes = $succes && $this->handleSequence($calendar, $index, $sequence))) {
                $this->error('Could not handle sequence ' . $calendar->label . ' -> ' . $index);
            }
        }

        return $succes;
    }

    /**
     * Check if the calendar is updated since the last import
     *
     * @param Calendar $calendar
     * @param array $sequences
     * @return bool
     */
    private function isCalendarUpdated(Calendar $calendar, array $sequences)
    {
        $isUpdated = false;

        // Check if every rule is in the database;
        foreach ($sequences as $sequence) {
            $startDate = $sequence['startDate'];
            $endDate = $sequence['endDate'];
            $untilDate = $sequence['untilDate'];

            $event = Event::where('rrule', $this->getCalendarRule($startDate, $endDate, $untilDate))
                ->where('start_date', $startDate->toIso8601String())
                ->where('end_date', $endDate->toIso8601String())
                ->where('until', $untilDate->endOfDay()->format('Y-m-d'))
                ->where('calendar_id', $calendar->id)
                ->first();

            if (is_null($event)) {
                $isUpdated = true;
                break;
            }
        }

        // Check if the amount of rules is correct
        if (Event::where('calendar_id', $calendar->id)->count() != count($sequences)) {
            $isUpdated = true;
        }

        return $isUpdated;
    }

    /**
     * Convert the sequence array to a event and save it to the calendar
     *
     * @param Calendar $calendar
     * @param $index
     * @param array $sequence
     */
    private function handleSequence(Calendar $calendar, $index, array $sequence)
    {
        $startDate = $sequence['startDate'];
        $endDate = $sequence['endDate'];
        $untilDate = $sequence['untilDate'];

        $event = new Event();
        $event->start_date = $startDate;
        $event->end_date = $endDate;
        $event->label = $index + 1;
        $event->until = $untilDate->endOfDay()->format('Y-m-d');
        $event->rrule = $this->getCalendarRule($startDate, $endDate, $untilDate);

        return (bool)$calendar->events()->save($event);
    }

    /**
     * Get the calendar rrule
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Carbon $untilDate
     * @return string
     */
    private function getCalendarRule(Carbon $startDate, Carbon $endDate, Carbon $untilDate)
    {
        if ($endDate->dayOfYear == $untilDate->dayOfYear) {
            return 'FREQ=YEARLY;BYMONTH=' . $startDate->month . ';BYMONTHDAY=' . $startDate->day;
        }

        return 'BYDAY=' . self::WEEKDAYS[$startDate->dayOfWeek] . ';FREQ=WEEKLY';
    }

    /**
     * Get all sequences from the list and put them in a more readable array,
     *
     * @param $list
     * @return array
     */
    private function getSequences($eventList, $year)
    {
        $transformedEventList = $this->transformEventList($eventList, $year);

        $sequences = [];

        foreach ($transformedEventList as $infoByDay) {
            foreach ($infoByDay as $info) {
                foreach ($info['sequences'] as $sequence) {
                    $sequences[] = $sequence;
                }
            }
        }

        return $sequences;
    }

    /**
     * Transform the recreatex list to a usable format so sequences can be detected
     *
     * @param $eventList
     * @return array
     */
    private function transformEventList(array $eventList, $year)
    {
        $transformedList = [];

        foreach ($eventList as $eventArr) {
            $eventDate = Carbon::createFromFormat('Y - m - d\TH:i:s', $eventArr['Date']);

            // Recreatex bug : api also returns the last day of the previous year
            if ($eventDate->year != $year) {
                continue;
            }

            // Store all openinghours in a array so sequences can be detected,
            // every item in the recreatex list containers to possible timespans
            $this->storeEventInList(
                $transformedList,
                $this->getStartDate($eventDate, $eventArr['From1']),
                $this->getEndDate($eventDate, $eventArr['To1'])
            );

            $this->storeEventInList(
                $transformedList,
                $this->getStartDate($eventDate, $eventArr['From2']),
                $this->getEndDate($eventDate, $eventArr['To2'])
            );
        }

        $this->completeEventList($transformedList);

        return $transformedList;
    }

    /**
     * After storing the data we are left with orphan data,
     * this orphan data is put in sequences and the unused indexed are removed
     *
     * @param $list
     */
    private function completeEventList(&$list)
    {
        foreach ($list as $key => $dayOfWeekInfo) {
            foreach (array_keys($dayOfWeekInfo) as $dayOfWeek) {
                if (!isset($list[$key][$dayOfWeek]['sequences'])) {
                    $list[$key][$dayOfWeek]['sequences'] = [];
                }

                $list[$key][$dayOfWeek]['sequences'][] = [
                    'startDate' => clone $list[$key][$dayOfWeek]['startDate'],
                    'endDate' => clone $list[$key][$dayOfWeek]['endDate'],
                    'untilDate' => clone $list[$key][$dayOfWeek]['untilDate'],
                ];

                unset($list[$key][$dayOfWeek]['startDate']);
                unset($list[$key][$dayOfWeek]['endDate']);
                unset($list[$key][$dayOfWeek]['untilDate']);
                unset($list[$key][$dayOfWeek]['lastWeekOfYear']);
            }
        }
    }

    /**
     * Put the recreatex event into an array so sequences can be detected
     *
     * @param $transformedArr
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     */
    private function storeEventInList(&$transformedArr, Carbon $startDate = null, Carbon $endDate = null)
    {
        // If the start or end date aren't given the list item is ignored
        if (is_null($startDate) || is_null($endDate)) {
            return;
        }

        // The start and and hour form the key
        $key = $startDate->format('H:i') . '-' . $endDate->format('H:i');

        $dayOfWeek = $startDate->dayOfWeek;
        $weekOfYear = $startDate->weekOfYear;

        // If the start/end hour where allready handled the week before the until date is changed
        // If we encounter the same start/end hour but more then a week later the sequence is saved
        // We end up with orphan data, but these will be put in sequences later on
        // This works because we sorted the array by date
        if (isset($transformedArr[$key][$dayOfWeek]['lastWeekOfYear']) &&
            $transformedArr[$key][$dayOfWeek]['lastWeekOfYear'] == $weekOfYear - 1) {
            $transformedArr[$key][$dayOfWeek]['untilDate'] = clone $endDate;
        } else {
            if (isset($transformedArr[$key][$dayOfWeek]['lastWeekOfYear'])) {
                $transformedArr[$key][$dayOfWeek]['sequences'][] = [
                    'startDate' => clone $transformedArr[$key][$dayOfWeek]['startDate'],
                    'endDate' => clone $transformedArr[$key][$dayOfWeek]['endDate'],
                    'untilDate' => clone $transformedArr[$key][$dayOfWeek]['untilDate'],
                ];
            }

            $transformedArr[$key][$dayOfWeek]['startDate'] = clone $startDate;
            $transformedArr[$key][$dayOfWeek]['endDate'] = clone $endDate;
            $transformedArr[$key][$dayOfWeek]['untilDate'] = clone $endDate;
        }

        $transformedArr[$key][$dayOfWeek]['lastWeekOfYear'] = $weekOfYear;
    }

    /**
     * Get the start date from a timestamp
     *
     * @param Carbon $eventDate
     * @param $timestamp
     * @return Carbon|null|static
     */
    private function getStartDate(Carbon $eventDate, $timestamp)
    {
        if (is_null($timestamp)) {
            return;
        }

        $date = Carbon::createFromFormat('Y - m - d\TH:i:s', $timestamp);

        // Catch the 00:00 case, the feed is supposed to deliver daily events
        // but to make a "full day open" 2 days are passed with the second day
        // having 00:00
        if (Str::contains($timestamp, '00:00:00')) {
            $date = clone $eventDate;
            $date->startOfDay();
        }

        return $date;
    }

    /**
     * Get the end date from a timestamp
     *
     * @param Carbon $eventDate
     * @param $timestamp
     * @return Carbon|null|static
     */
    private function getEndDate(Carbon $eventDate, $timestamp)
    {
        if (is_null($timestamp)) {
            return;
        }

        $date = Carbon::createFromFormat('Y - m - d\TH:i:s', $timestamp);

        // Catch the 00:00 case, the feed is supposed to deliver daily events
        // but to make a "full day open" 2 days are passed with the second day
        // having 00:00
        if (Str::contains($timestamp, '00:00:00')) {
            $date = clone $eventDate;
            $date->endOfDay();
        }

        return $date;
    }

    /**
     * Get a list of dates from the recreatex soap api
     *
     * @param Service $service
     * @param $year
     * @return array
     */
    protected function getOpeninghoursList($year)
    {
        $parameters = [
            'Context' => [
                'ShopId' => $this->shopId,
            ],
            'InfrastructureOpeningsSearchCriteria' => [
                'InfrastructureId' => $this->activeServiceRecord->identifier,
                'From' => $year . '-01-01T00:00:00.8115784+02:00',
                'Until' => ++$year . '-01-01T00:00:00.8115784+02:00',
            ],
        ];

        try {
            $response = $this->getClient()->FindInfrastructureOpenings($parameters);
            $transformedData = json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            $this->error('A problem in collecting external data from Recreatex for ' . $this->activeServiceRecord . ' with year ' .
                $year . ': ' . $e->getMessage());

            return false;
        }

        $key = 'InfrastructureOpenings.InfrastructureOpeningHours.InfrastructureOpeningHours.OpenHours.OpeningHour';

        return Arr::get($transformedData, $key, 0);
    }

    /**
     * Get the recreatex channel for service
     *
     * @return Channel
     */
    private function getOrCreateChannel($channelName = null)
    {
        // Look for a channel with the predefined channel name
        $channel = Channel::where('service_id', $this->activeServiceRecord->id)
            ->where('label', $channelName ?: $this->channelName)
            ->first();

        // If this channel doesn't exist a new one is created
        if (is_null($channel)) {
            $channel = new Channel(['label' => $channelName ?: $this->channelName]);
            $this->activeServiceRecord->channels()->save($channel);
        }

        return $channel;
    }

    /**
     * Get the openinghours object based on the channel and the year
     *
     * @param Channel $channel
     * @param $year
     * @return Openinghours
     */
    private function getOrCreateOpeninghours(Channel $channel, $year)
    {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';

        // Check if the openinghours object allready exists
        $openinghours = Openinghours::where('channel_id', $channel->id)
            ->where('start_date', $startDate)
            ->where('end_date', $endDate)
            ->first();

        // If the object doesn't exist is created
        if (is_null($openinghours)) {
            $openinghours = new Openinghours();
            $openinghours->active = true;
            $openinghours->label = 'Geïmporteerde kalender ' . $startDate . ' -' . $endDate;
            $openinghours->start_date = $startDate;
            $openinghours->end_date = $endDate;

            $channel->openinghours()->save($openinghours);
        }

        return $openinghours;
    }

    /**
     * Get the calendar for an openinghours object
     *
     * @param Openinghours $openinghours
     * @return Calendar
     */
    private function getOrCreateCalendar(Openinghours $openinghours)
    {
        // Check if the calendar object allready exists
        $calendar = Calendar::where('label', $this->calendarName)
            ->where('openinghours_id', $openinghours->id)
            ->first();

        // Create a new calendar object and link it to the previously created openinghour object
        if (is_null($calendar)) {
            $calendar = new Calendar();
            $calendar->priority = 0;
            $calendar->closinghours = 0;
            $calendar->label = $this->calendarName;

            $openinghours->calendars()->save($calendar);
        }

        return $calendar;
    }

    /**
     * Clear the channel from all openinghour objects
     *
     * @param Channel $channel
     */
    private function clearChannelOpeningHour(Channel $channel, $year)
    {
        $openinghours = Openinghours::where('channel_id', $channel->id)
            ->where('start_date', $year . '-01-01')
            ->where('end_date', $year . '-12-31')
            ->first();
        if (isset($openinghours->id)) {
            $openinghours->delete();
            $this->info('Child data removed from channel ' . $channel->id . ' for year ' . $year .
                'in  "' . $this->activeServiceRecord->label . '"');
        }
    }

    /**
     * Clear the calendar from all event objects
     *
     * @param Channel $channel
     */
    private function clearCalendar(Calendar $calendar)
    {
        $calendar->events()
            ->each(function (Event $event) {
                $event->delete();
            });
    }

    /**
     * Sort the raw event list from the recreatex soap service
     *
     * @param $list
     */
    private function sortEventList(&$list)
    {
        uasort($list, function ($eventA, $eventB) {
            return Carbon::createFromFormat('Y - m - d\TH:i:s', $eventA['Date'])
                ->gt(Carbon::createFromFormat('Y - m - d\TH:i:s', $eventB['Date']));
        });
    }

    /**
     * Write a string as error output.
     *
     * overwrite parent to make sure errors go to log
     *
     * @param  string $string
     * @param  null|int|string $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        \Log::error($string);
        parent::error($string, $verbosity);
    }

    /**
     * Write a string as info output.
     *
     * overwrite parent to make info go to log
     *
     * @param  string $string
     * @param  null|int|string $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        \Log::info($string);
        parent::info($string, $verbosity);
    }

    /**
     * @return \SoapClient
     */
    protected function getClient()
    {
        if (!$this->soapClient) {
            $this->soapClient = new \SoapClient(env('RECREATEX_URI') . '?wsdl');
        }

        return $this->soapClient;
    }

    /**
     * @param $year
     */
    public function setCalendarStartYear($year){
        $this->calendarStartYear = $year;
    }

    /**
     * @param $year
     */
    public function setCalendarEndYear($year){
        $this->calendarEndYear = $year;
    }
}
