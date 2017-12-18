{{--
  -- Template rendering the times information for an open day.
  --
  -- Variables:
  --   Variables:
  --       - @param array $dayHours : Array of from-until hours for a day.
  --}}
<span class="openinghours--status">@lang('openinghourApi.OPEN')</span>
@foreach($dayHours as $hours)
    @include('api.openinghours.time', array('hours' => $hours))
    @if(end($dayHours) != $hours)
        <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
    @endif
@endforeach
