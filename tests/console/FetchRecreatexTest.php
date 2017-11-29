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

    /**
     * @return array
     */
    private function getOpeninghourListMockupList()
    {
        $content = file_get_contents(__DIR__ . '/../data/console/openinghoursListMockupList.json');
        return json_decode($content, true);
    }

    /**
     * @return array
     */
    private function getEventListCriteria()
    {
        $content = file_get_contents(__DIR__ . '/../data/console/eventListCriteria.json');
        return json_decode($content, true);
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
            ->willReturn($this->getOpeninghourListMockupList());

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

        foreach ($this->getEventListCriteria() as $criteria){
            $events = Event::where($criteria)->get();
            $this->assertEquals(1,$events->count());
        }
    }
}