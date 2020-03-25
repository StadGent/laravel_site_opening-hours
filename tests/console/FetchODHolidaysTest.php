<?php

namespace Tests\Console;

use App\Models\DefaultCalendar;
use App\Models\DefaultEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FetchODHolidaysTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function testNewCalendarIsCreatedPerImportedIcal()
    {
        // cleanup db
        \DB::table('default_calendars')->delete();
        \DB::table('default_events')->delete();
        // set calendars + one fake
        $supportedCalendars = [
            'feestdagen' => 'https://datafiles.stad.gent/sites/default/files/feestdagen-nl-BE.ical',
            'schoolvakanties' => 'https://datafiles.stad.gent/sites/default/files/schoolvakanties-nl-BE.ical',
            'testCal' => 'https://datafiles.stad.gent/sites/default/files/testICal.ical',
        ];
        \Config::set('app.supported_exception_callendars', $supportedCalendars);

        //INIT RUN
        \Artisan::call('openinghours:fetch-od-holidays');
        // assert all calendars are added
        $this->assertCount(count($supportedCalendars), DefaultCalendar::all());
        // assert the calendars have now events
        $feestdagenCalendar = DefaultCalendar::where('label', 'feestdagen')->first();
        $feestdagenEventsCount = $feestdagenCalendar->events()->count();
        $schoolvakantiesEventsCount = DefaultCalendar::where('label', 'schoolvakanties')->first()->events()->count();

        $this->assertTrue(!!$feestdagenEventsCount);
        $this->assertTrue(!!$schoolvakantiesEventsCount);
        // except for the fake one
        $this->assertFalse(!!DefaultCalendar::where('label', 'testCal')->first()->events()->count());

        $extraEvent = DefaultEvent::create([
            'rrule' => 'BYDAY=MO,TU,WE,TH,FR;FREQ=WEEKLY',
            'start_date' => '2017-01-01 10:00:00',
            'end_date' => '2017-01-01 12:00:00',
            'calendar_id' => $feestdagenCalendar->id,
            'label' => 'extraTestEventThatMustBeCleanedUp',
        ]);

        // the event is linked on the calendar
        $this->assertTrue($feestdagenCalendar->events()->where('label', 'extraTestEventThatMustBeCleanedUp')->get()->isNotEmpty());
        $this->assertEquals(
            $feestdagenCalendar->events()->count(),
            ($feestdagenEventsCount + 1)
        );

        // FOLOW UP RUN
        \Artisan::call('openinghours:fetch-od-holidays');
        // extraTestEventThatMustBeCleanedUp was not found in the documents and should be cleaned up
        $this->assertFalse($feestdagenCalendar->events()->where('label', 'extraTestEventThatMustBeCleanedUp')->get()->isNotEmpty());
        // all other events should
        $this->assertEquals(
            DefaultCalendar::where('label', 'feestdagen')->first()->events()->count(),
            $feestdagenEventsCount
        );
        $this->assertEquals(
            DefaultCalendar::where('label', 'schoolvakanties')->first()->events()->count(),
            $schoolvakantiesEventsCount
        );
    }
}
