<?php
/** @var Carbon\Carbon $date */
$date = $dayInfoObj->date;
$isOpen = ! empty($dayInfoObj->hours);
$status = $isOpen ? 'OPEN' : 'CLOSED';
$dayName = $date->format('l');
$translatedDayName = trans('openinghourApi.' . $date->format('l'));
$dayOfMonth = $date->day;
$translatedMonthName = trans('openinghourApi.' . $date->format('F'));
$translatedStatus = trans('openinghourApi.' . $status);
$isSameYear = (new \Carbon\Carbon())->year == $date->year;
$specialDayName = null;
if ((new \Carbon\Carbon())->isSameDay($date)) {
    $specialDayName = trans('openinghourApi.TODAY');
} elseif ((new \Carbon\Carbon())->addDay()->isSameDay($date)) {
    $specialDayName = trans('openinghourApi.TOMORROW');
} elseif ((new \Carbon\Carbon())->subDay()->isSameDay($date)) {
    $specialDayName = trans('openinghourApi.YESTERDAY');
};

$referenceDate = clone $dayInfoObj->date;
$referenceDate->endOfDay();
$isDayPassed = (new \Carbon\Carbon())->greaterThan($referenceDate);

$outerClass = 'openinghours openinghours--details openinghours--day-' . strtolower($status);
if ($isDayPassed) {
    $outerClass .= ' openinghours--day-passed';
}

if (isset($type) && $isOpen) {
    $translatedStatus = trans('openinghourApi.' . $type);
}
?>
<div class="{{ $outerClass }}" property="openingHoursSpecification" typeof="OpeningHoursSpecification">
    <div class="openinghours--date{{ $specialDayName? " openinghours--special-day": ""}}{{ !$isSameYear? " openinghours--different-year": ""}}"
         property="validFrom validThrough" datetime="{{ $date->toDateString() }}">
        @if($specialDayName)
            <span class="openinghours--date-special-day">{{ $specialDayName }}</span><span
                    class="openinghours--date-between">, </span>
        @endif
        <span class="openinghours--date-day-of-week"><link property="dayOfWeek" href="http://schema.org/{{ $dayName }}">{{ $translatedDayName }}</span>
        <span class="openinghours--date-day">@lang('openinghourApi.DAY_OF_MONTH', ['DAY' => $dayOfMonth])</span>
        <span class="openinghours--date-month">{{ $translatedMonthName }}</span>
        @if(!$isSameYear)
            <span class="openinghours--date-year">{{ $date->year }}</span>
        @endif
    </div>
    <div class="openinghours--content">
        <div class="openinghours--times">
            <span class="openinghours--status">{{ $translatedStatus }}</span>
            @if($isOpen)
                @foreach($dayInfoObj->hours as $hours)
                    @php
                        $from = trans('openinghourApi.HH:MM', [
                                'HH' => substr($hours['from'],0,2),
                                'MM' => substr($hours['from'],3,2)
                                ]);
                        $from = preg_replace('/\D00$/', '', $from);
                        $from = preg_replace('/^0/', '', $from);

                        $until = trans('openinghourApi.HH:MM', [
                                'HH' => substr($hours['until'],0,2),
                                'MM' => substr($hours['until'],3,2)
                                ]);
                        $until = preg_replace('/\D00$/', '', $until);
                        $until = preg_replace('/^0/', '', $until);
                    @endphp
                    <div class="openinghours--time">
                        <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
                        <time property="opens" datetime="{{ $hours['from'] }}" aria-label="{{ $hours['from'] }}">
                            {{ $from }}&nbsp;@lang('openinghourApi.SHORT_HOUR')
                        </time>
                        <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
                        <time property="closes" datetime="{{ $hours['until'] }}" aria-label="{{ $hours['until']}}">
                            {{ $until }}&nbsp;@lang('openinghourApi.SHORT_HOUR')
                        </time>
                    </div>
                    @if(end($dayInfoObj->hours) != $hours)
                        <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>
