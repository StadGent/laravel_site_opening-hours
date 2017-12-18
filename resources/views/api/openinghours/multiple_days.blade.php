<div vocab="http://schema.org/" typeof="Library" class="openinghours openinghours--table">
    <h1>@lang('openinghourApi.title')</h1>
    @if($data[0]['openinghours'])
        @foreach($data[0]['openinghours'] as $dayInfoObj)
            <?php $dayString = \App\Models\DayInfo::WEEKDAYS[$dayInfoObj->date->dayOfWeek]; ?>
            <?php $isOpen = !empty($dayInfoObj->hours); ?>
            <div property="openingHoursSpecification" typeof="OpeningHoursSpecification" class="openinghours--day openinghours--day-{{$isOpen ? 'open' : 'closed'}}">
                <div class="openinghours--date">
                    <span class="openinghours--date--day-of-week"><link property="dayOfWeek" href="http://schema.org/{{ $dayString }}"/>@lang('openinghourApi.'.$dayString)</span>
                    <time property="validFrom validThrough" datetime="{{ $dayInfoObj->date->toDateString() }}">{{ $dayInfoObj->date->format('d/m/Y') }}</time>
                </div>
                <div class="openinghours--times">
                    <div class="openinghours--time">
                        @if($isOpen)
                            @include('api.openinghours.openinghour_times_open',['dayInfoObj' => $dayInfoObj])
                        @else
                            <time property="opens" datetime="00:00:00">@lang('openinghourApi.CLOSED')</time>
                            <time property="closes" datetime="00:00:00"></time>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>