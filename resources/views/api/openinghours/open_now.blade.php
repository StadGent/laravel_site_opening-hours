@foreach($data as $channelData)
    <div vocab=“http://schema.org/” typeof=“Library”>
        <h1>{{$channelData['channel']}}</h1>
        @if(isset($channelData['openNow']['status']))
            <div>@lang('openinghourApi.OPEN')</div>
        @else
            <div>@lang('openinghourApi.CLOSED')</div>
        @endif
    </div>
@endforeach
