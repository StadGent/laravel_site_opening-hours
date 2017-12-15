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