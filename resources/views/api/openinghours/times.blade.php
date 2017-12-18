{{--
  -- Template rendering the times information for a day.
  --
  -- Variables:
  --   Variables:
  --       - @param dayInfoObj $dayInfoObj : Day object.
  --}}
<div class="openinghours--times">
    @if(!empty($dayInfoObj->hours))
        @include('api.openinghours.times_open', ['dayHours' => $dayInfoObj->hours])
    @else
        @include('api.openinghours.times_closed')
    @endif
</div>
