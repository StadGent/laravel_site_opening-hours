<div vocab="http://schema.org/" typeof="Library" class="openinghours openinghours--table">
    <h1>Openinghours</h1>
    @if($data[0]['openinghours'])
        @foreach($data[0]['openinghours'] as $dayInfoObj)
            <?php $dayString = \App\Models\DayInfo::WEEKDAYS[$dayInfoObj->date->dayOfWeek]; ?>
            @if(empty($dayInfoObj->hours))
                <div property="openingHoursSpecification" typeof="OpeningHoursSpecification" class="openinghours--day openinghours--day-closed">
                    <div class="openinghours--date">
                        <span class="openinghours--date--day-of-week"><link property="dayOfWeek" href="http://schema.org/{{ $dayString }}"/>@lang('openinghourApi.'.$dayString)</span>
                        <time property="validFrom validThrough" datetime="{{ $dayInfoObj->date->toDateString() }}">{{ $dayInfoObj->date->format('d/m/Y') }}</time>
                    </div>
                    <div class="openinghours--times">
                        <div class="openinghours--time">
                            <time property="opens" datetime="00:00:00">@lang('openinghourApi.CLOSED')</time>
                            <time property="closes" datetime="00:00:00"></time>
                        </div>
                    </div>
                </div>
            @else
                <div property="openingHoursSpecification" typeof="OpeningHoursSpecification"
                     class="openinghours--day openinghours--day-open">
                    <div class="openinghours--date">
                        <span class="openinghours--date--day-of-week"><link property="dayOfWeek" href="http://schema.org/{{ $dayString }}">{{ $dayString }}</span>
                        <time property="validFrom validThrough" datetime="{{ $dayInfoObj->date->toDateString() }}">{{ $dayInfoObj->date->format('d/m/Y') }}</time>
                    </div>
                    <div class="openinghours--times">
                        <span class="openinghours--status">@lang('openinghourApi.OPEN')</span>
                        <div class="openinghours--time">
                            @foreach($dayInfoObj->hours as $hourArr)
                                <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
                                <time datetime="{{$hourArr['from']}}">{{$hourArr['from']}}</time>
                                <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
                                <time datetime="{{$hourArr['until']}}">{{$hourArr['until']}}</time>
                                @if(end($dayInfoObj->hours) != $hourArr)
                                    <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>