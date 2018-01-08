{{--
  -- Template to print out the Opening hours for a single month.
  --
  -- Variables:
  -- @param aray $data
  --     Data containing the Opening hours information.
  --}}
@if($data[0]['openinghours'])
    <?php $firstDay = reset($data[0]['openinghours'])->date; ?>
    <?php  $lastDay = end($data[0]['openinghours'])->date; ?>
    @inject('localeService', 'App\Services\LocaleService')
    <?php
    $weekdays = \App\Models\DayInfo::WEEKDAYS_SHORT;
    $addedValue = $localeService->getWeekStartDay();
    for ($i = 0; $i < $localeService->getWeekStartDay(); $i++) {
        $value = array_shift($weekdays);
        $weekdays[] = $value;
    }
    ?>
    <div class="openinghours openinghours--calendar">
        <div class="openinghours--header">
            <button class="openinghours--prev">@lang('openinghourApi.PREVIOUS')</button>
            <div class="openinghours--month">@lang('openinghourApi.'.$firstDay->format('F')) {{ $firstDay->format('Y') }}</div>
            <button class="openinghours--next">@lang('openinghourApi.NEXT')</button>
        </div>
        <div class="openinghours--days">
            @foreach($weekdays as $weekday)
                <div class="openinghours--day openinghours--day--day-of-week">@lang('openinghourApi.'.$weekday)</div>
            @endforeach
            @for($i=0;$i< $firstDay->dayOfWeek - $localeService->getWeekStartDay();$i++)
                <div class="openinghours--day openinghours--day-disabled"></div>
            @endfor
            @foreach($data[0]['openinghours'] as $dayInfoObj)
                <?php $isSameDay = (new \Carbon\Carbon())->isSameDay($dayInfoObj->date);?>
                <?php $isOpen = !empty($dayInfoObj->hours);?>
                <div class="openinghours--day openinghours--day-{{$isOpen ? 'open' : 'closed'}} {{$isSameDay ? 'openinghours--day-active' : ''}}">
                    <span>{{ $dayInfoObj->date->day }}</span>
                    <div class="openinghours openinghours--details openinghours--day-{{$isOpen ? 'open' : 'closed'}}">
                        <div class="openinghours--date">
                            <span class="openinghours--date-day">{{ $dayInfoObj->date->day }}</span>
                            <span class="openinghours--date-month">@lang('openinghourApi.'.$dayInfoObj->date->format('F'))</span>
                        </div>
                        <div class="openinghours--content">
                            @include('api.openinghours.times', ['dayInfoObj' => $dayInfoObj])
                        </div>
                    </div>
                </div>
            @endforeach
            @for($i=0;$i<7 - $lastDay->dayOfWeek -1 + $localeService->getWeekStartDay();$i++)
                <div class="openinghours--day openinghours--day-disabled"></div>
            @endfor
        </div>
    </div>
@endif
