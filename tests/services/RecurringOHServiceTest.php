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

class RecurringOHServiceTest extends \TestCase
{
    use DatabaseTransactions;

    /**
     * @var mixed
     */
    private $recurringOHService;

    public function setup()
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
        $startDate = Carbon::today()->startOfWeek();
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
        $service = factory(Service::class)->create();
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

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make();
        $openinghour = factory(Openinghours::class)->make(['start_date' => '2017-01-01', 'end_date' => '2017-12-31']);
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);

        $openinghour2 = factory(Openinghours::class)->create([
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $openinghour2->calendars()->saveMany(
            factory(Calendar::class, 5)->make(['openinghours_id' => $openinghour2->id])
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

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make(['label' => 'BALIE']);
        $openinghour = factory(Openinghours::class)->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31'
        ]);
        $calendar = factory(Calendar::class)->make(['closinghours' => 1]);
        $event = factory(Event::class)->make([
            'start_date' => '2017-01-01 08:00:00',
            'end_date' => '2017-01-01 17:00:00',
            'until' => '2017-12-31 17:00:00',
            'calendar_id' => $calendar->id,
        ]);
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);
        $openinghour->calendars()->save($calendar);
        $calendar->events()->save($event);

        $openinghour2 = factory(Openinghours::class)->create([
            'label' => 'Opening van 2018 tot 2019',
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $calendar2 = factory(Calendar::class)->make(['openinghours_id' => $openinghour2->id]);
        $openinghour2->calendars()->save($calendar2);

        $event2 = factory(Event::class)->make([
            'start_date' => '2018-01-01 08:00:00',
            'start_date' => '2018-01-01 13:00:00',
            'end_date' => '2018-01-01 17:00:00',
            'until' => '2018-12-31 17:00:00',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event2);

        $event3 = factory(Event::class)->make([
            'start_date' => '2018-01-01 08:00:00',
            'end_date' => '2018-01-01 12:00:00',
            'until' => '2018-12-31 12:00:00',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event3);

        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);

        $expected = <<<EOL
<h3>BALIE</h3>
<div>
<p>maandag tot vrijdag : gesloten, geldig t.e.m. zondag 31 december 2017</p>
</div>
<div>
<p>maandag tot vrijdag : 8 tot 12 uur en 13 tot 17 uur, geldig vanaf maandag 1 januari 2018</p>
</div>
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

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make(['label' => 'BALIE']);
        $openinghour = factory(Openinghours::class)->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31'
        ]);
        $calendar = factory(Calendar::class)->make(['closinghours' => 1]);
        $event = factory(Event::class)->make();
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);
        $openinghour->calendars()->save($calendar);
        $calendar->events()->save($event);

        $openinghour2 = factory(Openinghours::class)->create([
            'label' => 'Opening van 2018 tot 2019',
            'channel_id' => $channel->id,
            'start_date' => '2018-01-01',
            'end_date' => '2018-12-31',
        ]);
        $calendar2 = factory(Calendar::class)->make(['openinghours_id' => $openinghour2->id]);
        $openinghour2->calendars()->save($calendar2);

        $event2 = factory(Event::class)->make([
            'rrule' => 'FREQ=DAILY',
            'start_date' => '2018-10-01 00:00:00',
            'end_date' => '2018-10-01 23:59:59',
            'until' => '2018-10-01 23:59:59',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event2);
        $rrOutput = $this->recurringOHService->getServiceOutput($service, $startDate, $endDate);

        $expected = <<<EOL
<h3>BALIE</h3>
<div>
<p>maandag tot vrijdag : gesloten, geldig t.e.m. zondag 31 december 2017</p>
</div>
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
        $event = factory(Event::class)->make(['start_date' => '2099-01-01']);
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
        $event = factory(Event::class)->make(['until' => '1995-01-01']);
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

        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => '2015-05-01 00:00:00',
            'end_date' => '2015-05-01 23:59:59',
            'until' => '2099-05-01 23:59:59',
        ]);

        $event->calendar = new Calendar();

        $valid = $this->recurringOHService->validateEvent($event, $startDate, $endDate);
        $this->assertTrue($valid);
    }
}
