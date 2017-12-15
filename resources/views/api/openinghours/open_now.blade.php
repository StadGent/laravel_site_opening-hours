@if($data[0])
    <div vocab=“http://schema.org/” typeof=“Library”>
        <h1>{{$data[0]['channel']}}</h1>
        @if($data[0]['openNow']['status'])
            <div>@lang('openinghourApi.OPEN')</div>
        @else
            <div>@lang('openinghourApi.CLOSED')</div>
        @endif
    </div>
@endif