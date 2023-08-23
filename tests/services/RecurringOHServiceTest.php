<?php

namespace Tests\Services;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Event;
use App\Models\Openinghours;
use App\Models\Service;
use App\Services\RecurringOHService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RecurringOHServiceTest extends \BrowserKitTestCase
{
    use DatabaseTransactions;

    /**
     * @var mixed
     */
    private $recurringOHService;

    public function setUp(): void
    {
        parent::setUp();
        $this->recurringOHService = app(RecurringOHService::class);
    }

    /**
     * @test
     * @group validate
     */
    public function testAService()
    {
        $startDate = Carbon::create(2018,3,1)->startOfWeek();
        $endDate = $startDate->copy()->addMonths(3);

        $service = Service::find(1);
        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);
        $this->assertNotEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testEmptyServiceIsEmptyOutput()
    {
        $service = Service::factory()->create();
        $startDate = Carbon::today()->startOfWeek();
        $endDate = $startDate->copy()->addMonths(3);
        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);
        $this->assertEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testEmptyServiceIsEmptysomething()
    {
        $startDate = new Carbon('2017-12-25');
        $endDate = $startDate->copy()->addMonths(3);

        $service = Service::factory()->create();

        $channel = Channel::factory()->make();
        $openinghour = Openinghours::factory()->make(['start_date' => '2017-01-01', 'end_date' => '2017-12-31']);
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);

        $openinghour2 = Openinghours::factory()->create([
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $openinghour2->calendars()->saveMany(
            Calendar::factory(5)->make(['openinghours_id' => $openinghour2->id])
        );

        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);
        $this->assertEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testFullServiceWithMultipleOHsWillOrderAndAddGlue()
    {
        $startDate = new Carbon('2017-12-25');
        $endDate = $startDate->copy()->addMonths(3);

        $service = Service::factory()->create();

        $channel = Channel::factory()->make(['label' => 'BALIE']);
        $openinghour = Openinghours::factory()->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31'
        ]);
        $calendar = Calendar::factory()->make(['closinghours' => 1, 'published' => 1]);
        $event = Event::factory()->make([
            'start_date' => '2017-01-01 08:00:00',
            'end_date' => '2017-01-01 17:00:00',
            'until' => '2017-12-31 17:00:00',
            'calendar_id' => $calendar->id,
        ]);
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);
        $openinghour->calendars()->save($calendar);
        $calendar->events()->save($event);

        $openinghour2 = Openinghours::factory()->create([
            'label' => 'Opening van 2018 tot 2019',
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $calendar2 = Calendar::factory()->make(['openinghours_id' => $openinghour2->id, 'published' => 1]);
        $openinghour2->calendars()->save($calendar2);

        $event2 = Event::factory()->make([
            'start_date' => '2018-01-01 08:00:00',
            'start_date' => '2018-01-01 13:00:00',
            'end_date' => '2018-01-01 17:00:00',
            'until' => '2018-12-31 17:00:00',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event2);

        $event3 = Event::factory()->make([
            'start_date' => '2018-01-01 08:00:00',
            'end_date' => '2018-01-01 12:00:00',
            'until' => '2018-12-31 12:00:00',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event3);

        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);

        $expected = <<<EOL
BALIE<br />
Geldig t.e.m. zondag 31 december 2017<br />
maandag tot en met vrijdag<br /><br />
Geldig vanaf maandag 1 januari 2018<br />
maandag tot en met vrijdag: van 8 tot 12 uur en van 13 tot 17 uur<br /><br />
EOL;

        $expected = str_replace(PHP_EOL,'',$expected);

        $this->assertEquals($expected, str_replace("\n", '', $rrOutput));
    }

    /**
     * @test
     * @group validate
     */
    public function testFullServiceWithMultipleOHsButWithOneOHWithoutRelevantEvents()
    {
        $startDate = new Carbon('2017-12-25');
        $endDate = $startDate->copy()->addMonths(3);

        $service = Service::factory()->create();

        $channel = Channel::factory()->make(['label' => 'BALIE']);
        $openinghour = Openinghours::factory()->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31'
        ]);
        $calendar = Calendar::factory()->make(['closinghours' => 1, 'published' => 1]);
        $event = Event::factory()->make();
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);
        $openinghour->calendars()->save($calendar);
        $calendar->events()->save($event);

        $openinghour2 = Openinghours::factory()->create([
            'label' => 'Opening van 2018 tot 2019',
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $calendar2 = Calendar::factory()->make(['openinghours_id' => $openinghour2->id, 'published' => 1]);
        $openinghour2->calendars()->save($calendar2);

        $event2 = Event::factory()->make([
            'rrule' => 'FREQ=DAILY',
            'start_date' => '2018-10-01 00:00:00',
            'end_date' => '2018-10-01 23:59:59',
            'until' => '2018-10-01 23:59:59',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event2);
        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);

        $expected = <<<EOL
BALIE<br />
Geldig t.e.m. zondag 31 december 2017<br />
maandag tot en met vrijdag
<br /><br />
EOL;

        $expected = str_replace(PHP_EOL,'',$expected);

        $this->assertEquals($expected, str_replace("\n", '', $rrOutput));
    }

    /**
     * @test
     * @group validate
     */
    public function testValidateEventNotStarted()
    {
        $startDate = Carbon::today()->startOfWeek();
        $endDate = $startDate->copy()->addMonths(3);
        $event = Event::factory()->make(['start_date' => '2099-01-01']);
        $valid = $this->recurringOHService->validateEvent($event, $startDate, $endDate);
        $this->assertFalse($valid);
    }

    /**
     * @test
     * @group validate
     */
    public function testValidateEventAlreadyEnded()
    {
        $startDate = Carbon::today()->startOfWeek();
        $endDate = $startDate->copy()->addMonths(3);
        $event = Event::factory()->make(['until' => '1995-01-01']);
        $valid = $this->recurringOHService->validateEvent($event, $startDate, $endDate);
        $this->assertFalse($valid);
    }


    /**
     * event is in same year as start of periode
     * @test
     * @group validate
     */
    public function testValideYearlyEventInCurrentYear()
    {
        $startDate = new Carbon('2017-04-25');
        $endDate = $startDate->copy()->addMonths(3);

        $event = Event::factory()->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => '2015-05-01 00:00:00',
            'end_date' => '2015-05-01 23:59:59',
            'until' => '2099-05-01 23:59:59',
        ]);

        $event->calendar = new Calendar();
        $event->calendar->openinghours = new Openinghours();
        $event->calendar->openinghours->start_date = '2015-01-01';
        $event->calendar->openinghours->end_date = '2099-11-31';

        $valid = $this->recurringOHService->validateEvent($event, $startDate, $endDate);
        $this->assertTrue($valid);
    }
}
