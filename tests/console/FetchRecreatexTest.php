<?php

namespace Tests\Console;

use App\Console\Commands\FetchRecreatex;
use App\Models\Event;
use App\Models\Service;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FetchRecreatexTest extends \TestCase
{
    use DatabaseTransactions;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }


    private function getOpeninghourListValues()
    {
        $content = file_get_contents(__DIR__ . '/../data/console/fetchRecreatex.json');
        return json_decode($content, true);
    }

    private function getEventData()
    {
        return [
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=2",
                "start_date" => "2017-01-02 10:00:00",
                "end_date" => "2017-01-02 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=27",
                "start_date" => "2017-02-27 10:00:00",
                "end_date" => "2017-02-27 21:30:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-04-03 10:00:00",
                "end_date" => "2017-04-03 21:30:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-06-26 10:00:00",
                "end_date" => "2017-06-26 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=10;BYMONTHDAY=30",
                "start_date" => "2017-10-30 10:00:00",
                "end_date" => "2017-10-30 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=3",
                "start_date" => "2017-01-03 10:00:00",
                "end_date" => "2017-01-03 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=28",
                "start_date" => "2017-02-28 10:00:00",
                "end_date" => "2017-02-28 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-04-04 10:00:00",
                "end_date" => "2017-04-04 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-06-27 10:00:00",
                "end_date" => "2017-06-27 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-08-22 10:00:00",
                "end_date" => "2017-08-22 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=10;BYMONTHDAY=31",
                "start_date" => "2017-10-31 10:00:00",
                "end_date" => "2017-10-31 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=26",
                "start_date" => "2017-12-26 10:00:00",
                "end_date" => "2017-12-26 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=4",
                "start_date" => "2017-01-04 10:00:00",
                "end_date" => "2017-01-04 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=3;BYMONTHDAY=1",
                "start_date" => "2017-03-01 10:00:00",
                "end_date" => "2017-03-01 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-04-05 10:00:00",
                "end_date" => "2017-04-05 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-06-28 10:00:00",
                "end_date" => "2017-06-28 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=27",
                "start_date" => "2017-12-27 10:00:00",
                "end_date" => "2017-12-27 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=5",
                "start_date" => "2017-01-05 10:00:00",
                "end_date" => "2017-01-05 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=3;BYMONTHDAY=2",
                "start_date" => "2017-03-02 10:00:00",
                "end_date" => "2017-03-02 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-04-06 10:00:00",
                "end_date" => "2017-04-06 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-06-29 10:00:00",
                "end_date" => "2017-06-29 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=2",
                "start_date" => "2017-11-02 10:00:00",
                "end_date" => "2017-11-02 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=28",
                "start_date" => "2017-12-28 10:00:00",
                "end_date" => "2017-12-28 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=6",
                "start_date" => "2017-01-06 10:00:00",
                "end_date" => "2017-01-06 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=3;BYMONTHDAY=3",
                "start_date" => "2017-03-03 10:00:00",
                "end_date" => "2017-03-03 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-04-07 10:00:00",
                "end_date" => "2017-04-07 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=5;BYMONTHDAY=26",
                "start_date" => "2017-05-26 10:00:00",
                "end_date" => "2017-05-26 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-06-30 10:00:00",
                "end_date" => "2017-06-30 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-07-28 10:00:00",
                "end_date" => "2017-07-28 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=3",
                "start_date" => "2017-11-03 10:00:00",
                "end_date" => "2017-11-03 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=29",
                "start_date" => "2017-12-29 10:00:00",
                "end_date" => "2017-12-29 21:30:00",
            ],
            [
                "rrule" => "BYDAY=SA;FREQ=WEEKLY",
                "start_date" => "2017-01-07 09:00:00",
                "end_date" => "2017-01-07 19:00:00",
            ],
            [
                "rrule" => "BYDAY=SA;FREQ=WEEKLY",
                "start_date" => "2017-11-18 09:00:00",
                "end_date" => "2017-11-18 19:00:00",
            ],
            [
                "rrule" => "BYDAY=SU;FREQ=WEEKLY",
                "start_date" => "2017-01-08 09:00:00",
                "end_date" => "2017-01-08 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=4;BYMONTHDAY=17",
                "start_date" => "2017-04-17 09:00:00",
                "end_date" => "2017-04-17 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=5;BYMONTHDAY=1",
                "start_date" => "2017-05-01 09:00:00",
                "end_date" => "2017-05-01 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=5",
                "start_date" => "2017-06-05 09:00:00",
                "end_date" => "2017-06-05 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=5;BYMONTHDAY=25",
                "start_date" => "2017-05-25 09:00:00",
                "end_date" => "2017-05-25 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=7;BYMONTHDAY=21",
                "start_date" => "2017-07-21 09:00:00",
                "end_date" => "2017-07-21 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=8;BYMONTHDAY=15",
                "start_date" => "2017-08-15 09:00:00",
                "end_date" => "2017-08-15 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=1",
                "start_date" => "2017-11-01 09:00:00",
                "end_date" => "2017-11-01 18:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=11",
                "start_date" => "2017-11-11 09:00:00",
                "end_date" => "2017-11-11 18:00:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-01-09 13:30:00",
                "end_date" => "2017-01-09 19:00:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-03-06 13:30:00",
                "end_date" => "2017-03-06 19:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=4;BYMONTHDAY=24",
                "start_date" => "2017-04-24 13:30:00",
                "end_date" => "2017-04-24 19:00:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-05-08 13:30:00",
                "end_date" => "2017-05-08 19:00:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=19",
                "start_date" => "2017-06-19 13:30:00",
                "end_date" => "2017-06-19 19:00:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-09-04 13:30:00",
                "end_date" => "2017-09-04 19:00:00",
            ],
            [
                "rrule" => "BYDAY=MO;FREQ=WEEKLY",
                "start_date" => "2017-11-06 13:30:00",
                "end_date" => "2017-11-06 19:00:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-01-10 13:30:00",
                "end_date" => "2017-01-10 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-03-07 13:30:00",
                "end_date" => "2017-03-07 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-04-18 13:30:00",
                "end_date" => "2017-04-18 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=20",
                "start_date" => "2017-06-20 13:30:00",
                "end_date" => "2017-06-20 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-09-05 13:30:00",
                "end_date" => "2017-09-05 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TU;FREQ=WEEKLY",
                "start_date" => "2017-11-07 13:30:00",
                "end_date" => "2017-11-07 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-01-11 13:30:00",
                "end_date" => "2017-01-11 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-03-08 13:30:00",
                "end_date" => "2017-03-08 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-04-19 13:30:00",
                "end_date" => "2017-04-19 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=21",
                "start_date" => "2017-06-21 13:30:00",
                "end_date" => "2017-06-21 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-09-06 13:30:00",
                "end_date" => "2017-09-06 21:30:00",
            ],
            [
                "rrule" => "BYDAY=WE;FREQ=WEEKLY",
                "start_date" => "2017-11-08 13:30:00",
                "end_date" => "2017-11-08 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-01-12 13:30:00",
                "end_date" => "2017-01-12 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-03-09 13:30:00",
                "end_date" => "2017-03-09 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-04-20 13:30:00",
                "end_date" => "2017-04-20 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-06-01 13:30:00",
                "end_date" => "2017-06-01 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=22",
                "start_date" => "2017-06-22 13:30:00",
                "end_date" => "2017-06-22 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-09-07 13:30:00",
                "end_date" => "2017-09-07 21:30:00",
            ],
            [
                "rrule" => "BYDAY=TH;FREQ=WEEKLY",
                "start_date" => "2017-11-09 13:30:00",
                "end_date" => "2017-11-09 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-01-13 13:30:00",
                "end_date" => "2017-01-13 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-03-10 13:30:00",
                "end_date" => "2017-03-10 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-04-21 13:30:00",
                "end_date" => "2017-04-21 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-06-02 13:30:00",
                "end_date" => "2017-06-02 21:30:00",
            ],
            [
                "rrule" => "FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=23",
                "start_date" => "2017-06-23 13:30:00",
                "end_date" => "2017-06-23 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-09-01 13:30:00",
                "end_date" => "2017-09-01 21:30:00",
            ],
            [
                "rrule" => "BYDAY=FR;FREQ=WEEKLY",
                "start_date" => "2017-11-10 13:30:00",
                "end_date" => "2017-11-10 21:30:00",
            ],
        ];
    }

    /**
     * @test
     */
    public function testCalendarIsImported()
    {

        Service::where('source', 'recreatex')
            ->each(function (Service $service) {
                $service->delete();
            });

        $service = new Service();
        $service->source = 'recreatex';
        $service->label = 'Recreatex test service';
        $service->identifier = 'not-so-random-identifier';
        $service->draft = 0;
        $service->save();

        $commandMockup = $this->getMockBuilder(FetchRecreatex::class)
            ->setMethods(['getOpeninghoursList'])
            ->getMock();

        $commandMockup->expects($this->any())
            ->method('getOpeninghoursList')
            ->willReturn($this->getOpeninghourListValues());

        // We only provided the output for 1 year so we limit the years to the known data
        $commandMockup->setCalendarStartYear(2017);
        $commandMockup->setCalendarEndYear(2017);

        // Now we register our mocked command instance in console kernel
        $this->app[Kernel::class]->registerCommand($commandMockup);

        // Calling the command will run the mocked version of the command
        $this->artisan('openinghours:fetch-recreatex');

        // Now we check if the values are inserted into the database as expected
        $this->assertEquals(1, $service->channels->count());
        $channel = $service->channels->first();
        $this->assertEquals('Infrastructuur', $channel->label);
        $this->assertEquals(1, $channel->openinghours->count());
        $openinghours = $channel->openinghours->first();
        $this->assertEquals($openinghours->label, 'Geïmporteerde kalender2017-01-01 -2017-12-31');
        $this->assertEquals($openinghours->start_date, '2017-01-01');
        $this->assertEquals($openinghours->end_date, '2017-12-31');
        $this->assertCount(1, $openinghours->calendars);
        $calendar = $openinghours->calendars->first();
        $this->assertEquals('Openingsuren', $calendar->label);
        $this->assertEquals(75, $calendar->events->count());
        foreach ($this->getEventData() as $criteria){
            $events = Event::where($criteria)->get();
            $this->assertEquals(1,$events->count());
        }
    }
}