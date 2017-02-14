<?php

namespace App\Formatters;

use Carbon\Carbon;

/**
 * Returns a textual form of the openinghours of a service
 */
trait FormatsOpeninghours
{
    /**
     * Compute a week schedule for a service
     *
     * @param  int    $serviceId
     * @param  string $channel   The specific channel to print
     * @return array
     */
    private function formatWeek($serviceId, $channel = '')
    {
        $service = app('ServicesRepository')->getById($serviceId);

        $channels = [];

        // If no channel is passed, return all channels
        if (! empty($channel)) {
            $channels[] = $channel;
        } else {
            foreach ($service['channels'] as $object) {
                $channels[] = $object['label'];
            }
        }

        if (empty($channels)) {
            abort(404, 'Deze dienst heeft geen enkel kanaal met openingsuren.');
        }

        $openinghours = [];

        foreach ($channels as $channel) {
            $weekSchedule = $this->renderWeekForChannel($service['uri'], $channel);

            $openinghours[$channel] = $weekSchedule;
        }

        return $openinghours;
    }

    /**
     * Return the week schedule for a service and channel
     *
     * @param  string $serviceUri
     * @param  string $channel
     * @return array
     */
    private function renderWeekForChannel($serviceUri, $channel)
    {
        // Check if the service and channel exist
        $openinghours = app('OpeninghoursRepository')->getAllForServiceAndChannel($serviceUri, $channel);

        if (empty($openinghours)) {
            abort(404, 'Het gevraagde kanaal heeft geen openingsuren binnen de gevraagde dienst.');
        }

        $weekDays = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];

        // Get the openinghours that is active now
        $relevantOpeninghours = '';

        foreach ($openinghours as $openinghoursInstance) {
            if (Carbon::now()->between(
                (new Carbon($openinghoursInstance->start_date)),
                (new Carbon($openinghoursInstance->end_date))
            )) {
                $relevantOpeninghours = $openinghoursInstance;
                break;
            }
        }

        if (empty($relevantOpeninghours)) {
            // abort(404, 'No relevant openinghours found for this week.');
            return [];
        }

        // Go to the start of the week starting from today and iterate over every day
        // then check if there are events for that given day in the calendar, by priority
        $weekDay = Carbon::now();

        $week = [];

        for ($day = 0; $day <= 6; $day++) {
            $calendars = array_sort($relevantOpeninghours->calendars, function ($calendar) {
                return $calendar->priority;
            });

            $dayInfo = 'Gesloten';

            // Iterate all calendars for the day of the week
            foreach ($calendars as $calendar) {
                $ical = $this->createIcalFromCalendar($calendar);

                $extractedDayInfo = $this->extractDayInfo($ical, $weekDay->toDateString(), $weekDay->toDateString());

                if (! empty($extractedDayInfo)) {
                    $dayInfo = $calendar->closinghours ? 'Gesloten' : $extractedDayInfo;

                    break;
                }
            }

            $week[$weekDay->dayOfWeek] = $dayInfo;

            $weekDay->addDay();
        }

        $schedule = [];

        foreach ($week as $dayIndex => $daySchedule) {
            $schedule[] = $weekDays[$dayIndex] . ': ' . $daySchedule;
        }

        return $schedule;
    }
}
