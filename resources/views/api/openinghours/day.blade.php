@if($data[0]['openinghours'])
    @foreach($data[0]['openinghours'] as $dayInfoObj)
        <?php
            $isOpen = !empty($dayInfoObj->hours);
            $status = $isOpen ? 'OPEN' : 'CLOSED';
            $date = $dayInfoObj->date;
            $dayPrefix = NULL;
            if ((new \Carbon\Carbon())->isSameDay($dayInfoObj->date)) {
                $dayPrefix = 'TODAY';
            }
            elseif ((new \Carbon\Carbon())->addDay()->isSameDay($dayInfoObj->date)) {
                $dayPrefix = 'TOMORROW';
            }
        ?>
        <div class="openinghours openinghours--short">
            <div class="openinghours--day openinghours--day-{{ strtolower($status) }}">
                <div class="openinghours--times">
                    <span class="openinghours--date-day">@if($dayPrefix)@lang('openinghourApi.' . $dayPrefix )@else{{ $date->day }} @lang('openinghourApi.'.$date->format('F'))@endif</span>
                    <span class="openinghours--status">@lang('openinghourApi.' . $status)</span>
                    @if($isOpen)
                        @include('api/openinghours/times', array('dayHours' => $dayInfoObj->hours))
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif
