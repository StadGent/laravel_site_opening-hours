{{--Check if the channel has multiple channels--}}
<?php $hasMultipleChannels = count($data) > 1; ?>
@foreach($data as $channelData)
    {{--If multiple channels are present the channel name is printed--}}
    @if($hasMultipleChannels)
        <div class="channel-label">
            {{ $channelData['channel'] }}
        </div>
    @endif
    {{ $channelData['channelType'] }}
    {{--Looping over all openinghours objects--}}
    @foreach($channelData['openinghours'] as $dayInfoObj)
        @include('api.openinghours.day_info', ['dayInfoObj' => $dayInfoObj])
    @endforeach
@endforeach


