{{--
  -- Template rendeing a single time (from - to).
  --
  -- Variables:
  --   @param array $dayHours : Array containing array of hours (from-to).
  --}}
@foreach($dayHours as $hours)
    @include('api.openinghours.time', array('hours' => $hours))
    @if(end($dayHours) != $hours)
        <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
    @endif
@endforeach