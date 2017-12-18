{{--
  -- Template rendeing a single time (from - to).
  --
  -- Variables:
  --   @param array $hours : Array containing:
  --         - from : the hour from.
  --         - until : The hour until.
  --}}
<div class="openinghours--time">
    <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
    <time datetime="{{ $hours['from'] }}">{{ $hours['from'] }}</time>
    <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
    <time datetime="{{ $hours['until']}}">{{$hours['until']}}</time>
</div>
