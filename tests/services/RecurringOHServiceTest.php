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
        $service = Service::find(1);
        $rrOutput = $this->recurringOHService->getRecurringOHForService($service);
        $this->assertNotEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testEmptyServiceIsEmptyOutput()
    {
        $service = factory(Service::class)->create();
        $rrOutput = $this->recurringOHService->getRecurringOHForService($service);
        $this->assertEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testEmptyServiceIsEmptysomething()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 1]);

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make();
        $openinghour = factory(Openinghours::class)->make(['start_date' => '2017-01-01', 'end_date' => '2017-12-31']);
        $service->channels()->save($channel);
        $channel->openinghours()->save($openinghour);

        $openinghour2 = factory(Openinghours::class)->create(['channel_id' => $channel->id, 'start_date' => '2018-01-01', 'end_date' => '2018-12-31']);
        $openinghour2->calendars()->saveMany(
            factory(Calendar::class, 5)->make(['openinghours_id' => $openinghour2->id])
        );

        $rrOutput = $this->recurringOHService->getRecurringOHForService($service);
        $this->assertEmpty($rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testFullServiceWithMultipleOHs()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make(['label' => 'BALIE']);
        $openinghour = factory(Openinghours::class)->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31']);
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
            'end_date' => '2018-01-01 17:00:00',
            'until' => '2018-12-31 17:00:00',
            'calendar_id' => $calendar2->id,
        ]);
        $calendar2->events()->save($event2);

        $rrOutput = $this->recurringOHService->getRecurringOHForService($service);
        $expected = '<h2>BALIE</h2>' .
            '<div>' .
            '<h3>Normale uren geldig t.e.m. 31/12/2017</h3>' .
            '<p>Elke maandag tot vrijdag gesloten</p>' .
            '</div>' .
            '<div>' .
            '<h3>Normale uren geldig vanaf 01/01/2018</h3>' .
            '<p>Elke maandag tot vrijdag: open 08:00 - 17:00</p>' .
            '</div>';
        $this->assertEquals($expected, str_replace("\n", '', $rrOutput));
    }

    /**
     * @test
     * @group validate
     */
    public function testFullServiceWithMultipleOHsButWithOneOHWithoutRelevantEvents()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));

        $service = factory(Service::class)->create();

        $channel = factory(Channel::class)->make(['label' => 'BALIE']);
        $openinghour = factory(Openinghours::class)->make([
            'label' => 'Opening van 2017 tot 2018',
            'start_date' => '2017-01-01',
            'end_date' => '2017-12-31']);
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
        $rrOutput = $this->recurringOHService->getRecurringOHForService($service);
        $expected = '<h2>BALIE</h2>' .
            '<div>' .
            '<h3>Normale uren geldig t.e.m. 31/12/2017</h3>' .
            '<p>Elke maandag tot vrijdag gesloten</p>' .
            '</div>';
        $this->assertEquals($expected, str_replace("\n", '', $rrOutput));
    }

    /**
     * @test
     * @group validate
     */
    public function testValidateEventNotStarted()
    {
        $event = factory(Event::class)->make(['start_date' => '2099-01-01']);
        $valid = $this->recurringOHService->validateEvent($event);
        $this->assertFalse($valid);
    }

    /**
     * @test
     * @group validate
     */
    public function testValidateEventAlreadyEnded()
    {
        $event = factory(Event::class)->make(['until' => '1995-01-01']);
        $valid = $this->recurringOHService->validateEvent($event);
        $this->assertFalse($valid);
    }

    /**
     * @test
     * @group validate
     */
    public function testValidateYearlyEventOutOfPeriode()
    {
        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => Carbon::now()->subWeeks(4),
            'end_date' => Carbon::now()->subWeeks(2),
            'until' => Carbon::now()->addYear(2),
        ]);

        $valid = $this->recurringOHService->collectForEvent($event);
        $this->assertFalse($valid);
    }

    /**
     * event is in same year as start of periode
     * @test
     * @group validate
     */
    public function testValideYearlyEventInCurrentYear()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));

        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => '2015-05-01 00:00:00',
            'end_date' => '2015-05-01 23:59:59',
            'until' => '2099-05-01 23:59:59',
        ]);

        $valid = $this->recurringOHService->validateEvent($event);
        $this->assertTrue($valid);
    }

    /**
     * event is in other year as start of periode
     * @test
     * @group validate
     */
    public function testValideYearlyEventInNextYear()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 1]);

        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => new Carbon('2015-01-01-01 00:00:00'),
            'end_date' => new Carbon('2015-01-01 23:59:59'),
            'until' => new Carbon('2099-12-31 17:00:00'),
            'calendar' => $calendar,
        ]);

        $valid = $this->recurringOHService->validateEvent($event);
        $this->assertTrue($valid);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Op 01/01/2018 gesloten', $rrOutput);
    }

    /**
     * @test
     * @group validate
     */
    public function testValideYearlyEventPeriodeLeapingOverYear()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 1]);

        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=YEARLY',
            'start_date' => new Carbon('2015-12-25 00:00:00'),
            'end_date' => new Carbon('2016-01-02 23:59:59'),
            'until' => new Carbon('2099-01-02 23:59:59'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('25/12/2017 - 02/01/2018 gesloten', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testCollectForEvent()
    {
        $initStart = new Carbon('2017-12-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0]);
        $event = factory(Event::class)->make([
            'rrule' => 'BYDAY=MO,TU,WE,TH,FR;FREQ=WEEKLY',

            'start_date' => new Carbon('2015-01-01-01 08:30:00'),
            'end_date' => new Carbon('2015-01-01 17:00:00'),
            'until' => new Carbon('2099-12-31 17:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Elke maandag tot vrijdag: open 08:30 - 17:00', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testCollectForDaylyEvent()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0]);
        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=DAYLY',
            'start_date' => new Carbon('2017-05-01-01 08:30:00'),
            'end_date' => new Carbon('2017-05-01 17:00:00'),
            'until' => new Carbon('2017-05-01 17:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Op 01/05/2017: open 08:30 - 17:00', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testCollectForDaylyEventPeriode()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0]);
        $event = factory(Event::class)->make([
            'rrule' => 'FREQ=DAYLY',
            'start_date' => new Carbon('2017-05-01-01 08:30:00'),
            'end_date' => new Carbon('2017-05-05 17:00:00'),
            'until' => new Carbon('2017-05-05 17:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('01/05/2017 - 05/05/2017: open 08:30 - 17:00', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testSplitRrule()
    {
        $rRule = 'BYDAY=MO,TU,WE,TH,FR;FREQ=WEEKLY;BYSETPOS=2';
        $rRuleSplit = $this->recurringOHService->splitRrule($rRule);
        $rRuleSplitExpected = [
            'BYDAY' => 'MO,TU,WE,TH,FR',
            'FREQ' => 'WEEKLY',
            'BYSETPOS' => '2',
        ];

        $this->assertEquals($rRuleSplitExpected, $rRuleSplit);
    }

    /**
     * @test
     * @group content
     */
    public function testHrByDay()
    {
        $byDay = 'MO,TU,WE,TH';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('maandag tot donderdag', $byDayProcessed);

        $byDay = 'MO,TU,WE,TH,FR';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('maandag tot vrijdag', $byDayProcessed);

        $byDay = 'MO,TU,WE,TH,FR,SA';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('maandag tot zaterdag', $byDayProcessed);

        $byDay = 'SA,SU';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('zaterdag en zondag', $byDayProcessed);

        $byDay = 'MO,TU,WE,TH,FR,SA,SU';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('dag van de week', $byDayProcessed);

        $byDay = 'MO,WE,FR,SU';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('maandag, woensdag, vrijdag, zondag', $byDayProcessed);

        $byDay = 'TU,TH,SA';
        $byDayProcessed = $this->recurringOHService->hrByDay($byDay);
        $this->assertEquals('dinsdag, donderdag, zaterdag', $byDayProcessed);
    }

    /**
     * @test
     * @group content
     */
    public function testHrForNumber()
    {
        $hrBySetPos = $this->recurringOHService->hrForNumber(1);
        $this->assertEquals('1ste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(2);
        $this->assertEquals('2de', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(3);
        $this->assertEquals('3de', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(8);
        $this->assertEquals('8ste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(11);
        $this->assertEquals('11de', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(20);
        $this->assertEquals('20ste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(22);
        $this->assertEquals('22ste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(31);
        $this->assertEquals('31ste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(-1);
        $this->assertEquals('laatste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(-2);
        $this->assertEquals('voorlaatste', $hrBySetPos);
        $hrBySetPos = $this->recurringOHService->hrForNumber(-3);
        $this->assertEquals('2 na laatste', $hrBySetPos);
    }

    /**
     * @test
     * @group content
     */
    public function testHrEventAvailabilityFrom()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0, 'priority' => -1]);
        $event = factory(Event::class)->make([
            'rrule' => 'BYDAY=MO;FREQ=WEEKLY',
            'start_date' => new Carbon('2017-05-01-01 08:30:00'),
            'end_date' => new Carbon('2017-05-01 17:00:00'),
            'until' => new Carbon('2017-12-25 00:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Elke maandag: open 08:30 - 17:00 geldig vanaf 01/05/2017', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testHrEventAvailabilityUntil()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0, 'priority' => -1]);
        $event = factory(Event::class)->make([
            'rrule' => 'BYDAY=MO;FREQ=WEEKLY',
            'start_date' => new Carbon('2017-01-02-01 08:30:00'),
            'end_date' => new Carbon('2017-01-02 17:00:00'),
            'until' => new Carbon('2017-05-01 00:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Elke maandag: open 08:30 - 17:00 geldig t.e.m. 01/05/2017', $rrOutput);
    }

    /**
     * @test
     * @group content
     */
    public function testHrByMonthDay()
    {
        $initStart = new Carbon('2017-04-25');
        $this->recurringOHService->setStartPeriod($initStart);
        $this->recurringOHService->setEndPeriod($initStart->copy()->addMonths(3));
        $calendar = factory(Calendar::class)->make(['closinghours' => 0, 'priority' => -1]);
        $event = factory(Event::class)->make([
            'rrule' => 'BYMONTHDAY=15;FREQ=MONTHLY',
            'start_date' => new Carbon('2017-01-15-01 08:30:00'),
            'end_date' => new Carbon('2017-01-15 17:00:00'),
            'until' => new Carbon('2017-12-15 17:00:00'),
            'calendar' => $calendar,
        ]);

        $rrOutput = $this->recurringOHService->collectForEvent($event);
        $this->assertEquals('Elke 15de van de maand: open 08:30 - 17:00', $rrOutput);
    }
}
