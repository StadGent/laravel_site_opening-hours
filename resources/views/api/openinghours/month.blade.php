@if($data[0]['openinghours'])
    <?php $firstDay = reset($data[0]['openinghours'])->date; ?>
    <?php  $lastDay = end($data[0]['openinghours'])->date; ?>
    @inject('localeService', 'App\Services\LocaleService')
    <?php
    $weekdays = \App\Models\DayInfo::WEEKDAYS_SHORT;
    for ($i = 0; $i < $localeService->getWeekStartDay(); $i++) {
        $value = array_shift($weekdays);
        $weekdays[] = $value;
    }
    ?>
    <div class="openinghours openinghours--calendar">
        <div class="openinghours--header">
            <a href="#" class="openinghours--prev">@lang('openinghourApi.PREVIOUS')</a>
            <div class="openinghours--month">@lang('openinghourApi.'.$firstDay->format('F')) {{ $firstDay->format('Y') }}</div>
            <a href="#" class="openinghours--next">@lang('openinghourApi.NEXT')</a>
        </div>
        <div class="openinghours--days">
            @foreach($weekdays as $weekday)
                <div class="openinghours--day openinghours--day--day-of-week">@lang('openinghourApi.'.$weekday)</div>
            @endforeach

            @for($i=0;$i<((7 - $firstDay->dayOfWeek - $localeService->getWeekStartDay()) % 7);$i++)
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
                            <div class="openinghours--times">
                                @if($isOpen)
                                    <span class="openinghours--status">@lang('openinghourApi.OPEN')</span>
                                    <div class="openinghours--time">
                                        @foreach($dayInfoObj->hours as $hourArr)
                                            <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
                                            <time datetime="{{$hourArr['from']}}">{{$hourArr['from']}}</time>
                                            <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
                                            <time datetime="{{$hourArr['until']}}">{{$hourArr['until']}}</time>
                                            @if(end($dayInfoObj->hours) != $hourArr)
                                                <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="openinghours--status">@lang('openinghourApi.CLOSED')</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            @for($i=0;$i<((7 - $lastDay->dayOfWeek + $localeService->getWeekEndDay()) % 7);$i++)
                <div class="openinghours--day openinghours--day-disabled"></div>
            @endfor
        </div>
    </div>
@endif
