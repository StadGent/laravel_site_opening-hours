{{--
  -- Template to print out the Opening hours for multiple days.
  --
  -- Used for week and period calls.
  --
  -- Variables:
  -- @param aray $data
  --     Data containing the Opening hours information.
  --}}
<div vocab="http://schema.org/" typeof="Library" class="openinghours openinghours--table">
    <h1>@lang('openinghourApi.title')</h1>
    @if($data[0]['openinghours'])
        @foreach($data[0]['openinghours'] as $dayInfoObj)
            <?php $dayString = \App\Models\DayInfo::WEEKDAYS[$dayInfoObj->date->dayOfWeek]; ?>
            <?php $isOpen = !empty($dayInfoObj->hours); ?>
            <div property="openingHoursSpecification" typeof="OpeningHoursSpecification" class="openinghours--day openinghours--day-{{ $isOpen ? 'open' : 'closed' }}">
                <div class="openinghours--date">
                    <span class="openinghours--date--day-of-week"><link property="dayOfWeek" href="http://schema.org/{{ $dayString }}"/>@lang('openinghourApi.'.$dayString)</span>
                    <time property="validFrom validThrough" datetime="{{ $dayInfoObj->date->toDateString() }}">{{ $dayInfoObj->date->format('d/m/Y') }}</time>
                </div>
                @include('api.openinghours.times', ['dayInfoObj' => $dayInfoObj])
            </div>
        @endforeach
    @endif
</div>