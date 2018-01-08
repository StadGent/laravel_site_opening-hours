@inject('localeService', 'App\Services\LocaleService')
{{--Check if the channel has multiple channels--}}
<?php $hasMultipleChannels = count($data) > 1; ?>
@foreach($data as $channelData)
    {{--If multiple channels are present the channel name is printed--}}
    @if($hasMultipleChannels)
        <div class="channel-label">
            {{ $channelData['channel'] }}
        </div>
    @endif
    <?php
    /** @var \Carbon\Carbon $firstDay */
    $firstDay = reset($channelData['openinghours'])->date;
    $lastDay = end($channelData['openinghours'])->date;
    $weekdays = \App\Models\DayInfo::WEEKDAYS_SHORT;
    $addedValue = $localeService->getWeekStartDay();
    for ($i = 0; $i < $localeService->getWeekStartDay(); $i++) {
        $value = array_shift($weekdays);
        $weekdays[] = $value;
    }
    ?>
    <div vocab="http://schema.org/" typeof="Library" class="openinghours openinghours--calendar">
        <div class="openinghours--header">
            <button class="openinghours--prev">@lang('openinghourApi.PREVIOUS')</button>
            <div class="openinghours--month">@lang('openinghourApi.'.$firstDay->format('F')) {{ $firstDay->format('Y') }}</div>
            <button class="openinghours--next">@lang('openinghourApi.NEXT')</button>
        </div>
        <ul class="openinghours--days">
            @foreach($weekdays as $weekday)
                <li aria-hidden="true" class="openinghours--day openinghours--day--day-of-week">@lang('openinghourApi.'.$weekday)</li>
            @endforeach
            @for($i=0;$i< $firstDay->dayOfWeek - $localeService->getWeekStartDay();$i++)
                <li aria-hidden="true" class="openinghours--day openinghours--day-disabled"></li>
            @endfor
            @foreach($channelData['openinghours'] as $dayInfoObj)
                <?php
                    $isSameDay = (new Carbon\Carbon())->isSameDay($dayInfoObj->date);
                    $currentDay = $dayInfoObj->date->day;
                    $tabIndex = 0;
                    if($isSameDay){
                        $tabIndex = -1;
                    }elseif (
                        $dayInfoObj->date->day == 1 &&
                        !$firstDay->isSameDay((new \Carbon\Carbon())->firstOfMonth())
                    ){
                        $tabIndex = -1;
                    }
                    ?>
                <li aria-setsize="30" aria-posinset="{{ $currentDay }}" tabindex="{{ $tabIndex }}" @if($isSameDay)class="openinghours--day-active"@endif>
                    <span aria-hidden="true">{{ $dayInfoObj->date->day }}</span>
                    @include('api.openinghours.day_info', ['dayInfoObj' => $dayInfoObj])
                </li>
            @endforeach
            @for($i=0;$i<7 - $lastDay->dayOfWeek -1 + $localeService->getWeekStartDay();$i++)
                <li aria-hidden="true" class="openinghours--day openinghours--day-disabled"></li>
            @endfor
        </ul>
    </div>
@endforeach