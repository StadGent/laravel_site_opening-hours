@php
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
@endphp

@if($includeRFDa)
    <div class="openinghours--time">
        <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
        <time property="opens"
              datetime="{{ $hours['from'] }}"
              aria-label="{{ $hours['from'] }}">{{ $from }}&nbsp;@lang('openinghourApi.SHORT_HOUR')</time>
        <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
        <time property="closes"
              datetime="{{ $hours['until'] }}"
              aria-label="{{ $hours['until']}}">{{ $until }}&nbsp;@lang('openinghourApi.SHORT_HOUR')</time>
    </div>
@else
    <div class="openinghours--time">
        <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
        <time datetime="{{ $hours['from'] }}"
              aria-label="{{ $hours['from'] }}">{{ $from }}&nbsp;@lang('openinghourApi.SHORT_HOUR')</time>
        <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
        <time datetime="{{ $hours['until'] }}"
              aria-label="{{ $hours['until']}}">{{ $until }}&nbsp;@lang('openinghourApi.SHORT_HOUR')</time>
    </div>
@endif
@if($last)
    <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
@endif
