@php
    $allDay = $hours['from'] === '00:00' && $hours['until'] === '23:59';
    $from = trans('openinghourApi.HH:MM', [
            'HH' => substr($hours['from'],0,2),
            'MM' => substr($hours['from'],3,2)
            ]);
    $until = trans('openinghourApi.HH:MM', [
            'HH' => substr($hours['until'],0,2),
            'MM' => substr($hours['until'],3,2)
            ]);
    if(isset($short) && $short == true) {
        $from = preg_replace('/\D00$/', '', $from);
	    $from = preg_replace('/^0/', '', $from);
	    $until = preg_replace('/\D00$/', '', $until);
	    $until = preg_replace('/^0/', '', $until);
    }
    $wrapperClasses = "openinghours--time";
    if($allDay) {
      $wrapperClasses .= " all-day";
    }
@endphp

@if($includeRFDa)
    <div class="{{ $wrapperClasses }}">
        <span class="openinghours--time-prefix">
          @if(!$allDay)
            @lang('openinghourApi.FROM_HOUR')&#32;
          @endif
        </span>
        <time property="opens"
              datetime="{{ $hours['from'] }}"
              aria-label="{{ $hours['from'] }}">
          @if(!$allDay)
            {{ $from }}&nbsp;@lang('openinghourApi.SHORT_HOUR')&#32;
          @endif
        </time>
        @if(!$allDay)
          <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
        @else
          @lang('openinghourApi.ALL_DAY')
        @endif
        <time property="closes"
              datetime="{{ $hours['until'] }}"
              aria-label="{{ $hours['until']}}">
          @if(!$allDay)
            &#32;{{ $until }}&nbsp;@lang('openinghourApi.SHORT_HOUR')&#32;
          @endif
        </time>
    </div>
@else
    <div class="{{ $wrapperClasses }}">
        <span class="openinghours--time-prefix">
          @if(!$allDay)
            @lang('openinghourApi.FROM_HOUR')
          @endif
        </span>
        <time datetime="{{ $hours['from'] }}"
              aria-label="{{ $hours['from'] }}">
          @if(!$allDay)
            {{ $from }}&nbsp;@lang('openinghourApi.SHORT_HOUR')&#32;
          @endif
        </time>
        @if(!$allDay)
          <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
        @else
          @lang('openinghourApi.ALL_DAY')
        @endif
        <time datetime="{{ $hours['until'] }}"
              aria-label="{{ $hours['until']}}">
          @if(!$allDay)
            &#32;{{ $until }}&nbsp;@lang('openinghourApi.SHORT_HOUR')&#32;
          @endif
        </time>
    </div>
@endif
@if($last)
    <div class="openinghours--times-between">@lang('openinghourApi.AND')&#32;</div>
@endif
