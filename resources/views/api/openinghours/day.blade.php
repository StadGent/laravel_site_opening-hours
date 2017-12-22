{{--
  -- Template to print out the Opening hours for a single day.
  --
  -- Variables:
  -- @param aray $data
  --     Data containing the Opening hours information.
  --}}
@if($data[0]['openinghours'])
    @foreach($data[0]['openinghours'] as $dayInfoObj)
        <?php
        $isOpen = !empty($dayInfoObj->hours);
        $status = $isOpen ? 'OPEN' : 'CLOSED';
        $date = $dayInfoObj->date;
        $dayName = 'openinghourApi.' . $date->format('F');
        $dayPrefix = null;
        if ((new \Carbon\Carbon())->isSameDay($date)) {
            $dayPrefix = 'openinghourApi.TODAY';
        } elseif ((new \Carbon\Carbon())->addDay()->isSameDay($date)) {
            $dayPrefix = 'openinghourApi.TOMORROW';
        }
        ?>
        <div class="openinghours openinghours--short">
            <div class="openinghours--day openinghours--day-{{ strtolower($status) }}">
                <div class="openinghours--date">
                    <time property="validFrom validThrough" datetime="{{ $date->toDateString() }}">@if($dayPrefix)@lang($dayPrefix)@else{{ $date->day }} @lang($dayName)@endif</time>
                </div>
                @include('api.openinghours.times', ['dayInfoObj' => $dayInfoObj])
            </div>
        </div>
    @endforeach
@endif
