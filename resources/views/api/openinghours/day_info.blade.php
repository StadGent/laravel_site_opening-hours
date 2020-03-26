<?php
/** @var Carbon\Carbon $date */
$date = $dayInfoObj->date;
$includeRFDa = isset($includeRFDa) ? $includeRFDa : false;
$short = isset($short) ? $short: false;
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

$dateClass = 'openinghours--date';
if ($specialDayName) {
    $dateClass .= ' openinghours--special-day';
}
if (!$isSameYear) {
    $dateClass .= ' openinghours--different-year';
}

if (isset($type) && $isOpen) {
    $translatedStatus = trans('openinghourApi.' . $type);
}
?>

@if($includeRFDa)
    <div class="{{ $outerClass }}"
         property="openingHoursSpecification"
         typeof="OpeningHoursSpecification">
        <div class="{{ $dateClass }}"
             property="validFrom validThrough"
             datetime="{{ $date->toDateString() }}">
            @if($specialDayName)
                <span class="openinghours--date-special-day">{{ $specialDayName }}</span>
                <span class="openinghours--date-between">, </span>
            @endif
            <span class="openinghours--date-day-of-week">
                <link property="dayOfWeek"
                      href="http://schema.org/{{ $dayName }}">{{ $translatedDayName }}</span>
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
                        @include('api.openinghours.hours', [
                            'hours' => $hours,
                            'last' => end($dayInfoObj->hours) != $hours,
                            'includeRFDa' => $includeRFDa,
                            'short' => $short] )
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@else
    <div class="{{ $outerClass }}">
        <div class="{{ $dateClass }}">
            @if($specialDayName)
                <span class="openinghours--date-special-day">{{ $specialDayName }}</span>
                <span class="openinghours--date-between">, </span>
            @endif
            <span class="openinghours--date-day-of-week">{{ $translatedDayName }}</span>
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
                        @include('api.openinghours.hours', [
                            'hours' => $hours,
                            'last' => end($dayInfoObj->hours) != $hours,
                            'includeRFDa' => $includeRFDa,
                            'short' => $short] )
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endif
