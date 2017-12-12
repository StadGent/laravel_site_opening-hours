@if($data[0]['openinghours'])
    @foreach($data[0]['openinghours'] as $dayInfoObj)
        <div class="openinghours openinghours--short">
            @if(empty($dayInfoObj->hours))
                <div class="openinghours--day openinghours--day-closed">
                    <div class="openinghours--times">
                        <span class="openinghours--status">@lang('openinghourApi.CLOSED')</span>
                    </div>
                </div>
            @else
                <div class="openinghours--day openinghours--day-open">
                    <div class="openinghours--times">
                        <span class="openinghours--status">@lang('openinghourApi.OPEN')</span>
                        <div class="openinghours--time">
                            @foreach($dayInfoObj->hours as $hourArr)
                                <span class="openinghours--time-prefix">@lang('openinghourApi.FROM_HOUR')</span>
                                <time datetime="{{$hourArr['from']}}">{{$hourArr['from']}}</time>
                                <span class="openinghours--time-separator">@lang('openinghourApi.UNTIL_HOUR')</span>
                                <time datetime="{{$hourArr['until']}}">{{$hourArr['until']}}</time>
                                @if(end($dayInfoObj->hours) != $hourArr)
                                    <div class="openinghours--times-between">@lang('openinghourApi.AND')</div>'
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endif