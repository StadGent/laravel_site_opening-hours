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

    $monthStartDay = $firstDay->dayOfWeek;
    // This is 0 if the week starts on sunday and 1 if the week starts on monday
    $weekStartDay = $localeService->getWeekStartDay();

    $disabledDaysBeforeStart = $monthStartDay - $weekStartDay;
    if ($disabledDaysBeforeStart < 0) {
        $disabledDaysBeforeStart += 7;
    }

    $disabledDaysAfterEnd = 7 - $lastDay->dayOfWeek - 1 + $weekStartDay;
    if ($disabledDaysAfterEnd >= 7) {
        $disabledDaysAfterEnd -= 7;
    }

    $hasNextIteration = $transformer->hasNextIteration($channelData['channelId']);
    $hasPreviousIteration = $transformer->hasPreviousIteration($channelData['channelId']);

    ?>
    <div vocab="http://schema.org/" class="openinghours openinghours--calendar">
        <div class="openinghours--header">
            <button class="openinghours--prev" @if(!$hasPreviousIteration)disabled="disabled"@endif>
                @lang('openinghourApi.PREVIOUS')
            </button>
            <div class="openinghours--month">
                @lang('openinghourApi.'.$firstDay->format('F')) {{ $firstDay->format('Y') }}
            </div>
            <button class="openinghours--next" @if(!$hasNextIteration)disabled="disabled"@endif>
                @lang('openinghourApi.NEXT')
            </button>
        </div>
        <ul class="openinghours--days">
            @foreach($weekdays as $weekday)
                <li aria-hidden="true"
                    class="openinghours--day openinghours--day--day-of-week">@lang('openinghourApi.'.$weekday)</li>
            @endforeach
            @for($i=0;$i< $disabledDaysBeforeStart;$i++)
                <li aria-hidden="true" class="openinghours--day openinghours--day-disabled"></li>
            @endfor
            @foreach($channelData['openinghours'] as $dayInfoObj)
                <?php
                $referenceDate = clone $dayInfoObj->date;

                $referenceDate->endOfDay();
                $isSameDay = (new Carbon\Carbon())->isSameDay($referenceDate);
                $currentDay = $referenceDate->day;
                $tabIndex = -1;
                $isOpen = !empty($dayInfoObj->hours);
                $status = $isOpen ? 'OPEN' : 'CLOSED';

                if ($isSameDay) {
                    $tabIndex = 0;
                } elseif (
                    $referenceDate->day == 1 &&
                    !$firstDay->isSameDay((new \Carbon\Carbon())->firstOfMonth())
                ) {
                    $tabIndex = 0;
                }

                $isDayPassed = (new \Carbon\Carbon())->greaterThan($referenceDate);

                $class = 'openinghours--day openinghours--day-'.strtolower($status);
                if($isSameDay){
                    $class .= ' openinghours--day-active';
                }
                if($isDayPassed){
                    $class .= ' openinghours--day-passed';
                }

                ?>
                <li aria-setsize="30" aria-posinset="{{ $currentDay }}" tabindex="{{ $tabIndex }}"
                    class="{{ $class }}">
                    <span aria-hidden="true">{{ $referenceDate->day }}</span>
                    @include('api.openinghours.day_info_short', ['dayInfoObj' => $dayInfoObj])
                </li>
            @endforeach
            @for($i=0;$i< $disabledDaysAfterEnd;$i++)
                <li aria-hidden="true" class="openinghours--day openinghours--day-disabled"></li>
            @endfor
        </ul>
    </div>
@endforeach
