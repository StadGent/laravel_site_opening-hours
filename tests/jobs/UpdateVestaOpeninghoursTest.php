<?php

namespace Tests\Jobs;

use App\Formatters\Openinghours\HtmlFormatter;
use App\Jobs\UpdateVestaOpeninghours;
use App\Models\Channel;
use App\Models\Service;
use App\Services\OpeninghoursService;
use App\Services\VestaService;
use Carbon\Carbon;


class UpdateVestaOpeninghoursTest extends \TestCase {

  protected $vestaUid;
  protected $serviceId;

  protected function setUp()
  {
      parent::setUp();
      $this->vestaUid = uniqid();
      $this->serviceId = 1;
  }

  public function testHandle()
  {
      $job = new UpdateVestaOpeninghours($this->vestaUid, $this->serviceId);
      $openinghoursService = $this->getMockBuilder(OpeninghoursService::class)->disableOriginalConstructor()->getMock();
      $vestaService = $this->getMockBuilder(VestaService::class)->disableOriginalConstructor()->getMock();
      $formatter = $this->getMockBuilder(HtmlFormatter::class)->disableOriginalConstructor()->getMock();
      $expectedStart = (new Carbon())->startOfWeek();
      $expectedEnd = (new Carbon())->endOfWeek();
      $data = uniqid();
      $output = uniqid();
      $openinghoursService
          ->expects($this->once())
          ->method('collectData')
          ->with($this->callback(function (Carbon $start, Carbon $end, Service $service, Channel $channel = null) use ($expectedStart, $expectedEnd) {
          return $start->getTimestamp() === $expectedStart->getTimestamp()
              && $end->getTimestamp() === $expectedEnd->getTimestamp()
              && $service->id === 1
              && is_null($channel);
      }))->willReturnSelf();
      $openinghoursService
          ->expects($this->once())
          ->method('getData')
          ->willReturn($data);

      $formatter->expects($this->once())->method('render')->with($data)->willReturnSelf();
      $formatter->expects($this->once())->method('getOutput')->willReturn($output);

      $vestaService->expects($this->once())->method('updateOpeninghours')->with($output)->willReturn(true);
      
      $job->handle($openinghoursService, $vestaService, $formatter);
  }
}
