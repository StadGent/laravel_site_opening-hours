{{--Check if the channel has multiple channels--}}
<?php $hasMultipleChannels = count($data) > 1; ?>
@foreach($data as $channelData)
    {{--If multiple channels are present the channel name is printed--}}
    @if($hasMultipleChannels)
        <div class="channel-label">
            {{ $channelData['channel'] }}
        </div>
    @endif
    <div vocab="http://schema.org/" typeof="Library" class="openinghours openinghours--list">
        <ul class="openinghours--days">
            @foreach($channelData['openinghours'] as $dayInfoObj)
                <?php $isSameDay = (new Carbon\Carbon())->isSameDay($dayInfoObj->date); ?>
                <li @if($isSameDay)class="openinghours--day-active"@endif>
                    @include('api.openinghours.day_info', ['dayInfoObj' => $dayInfoObj])
                </li>
            @endforeach
        </ul>
    </div>
@endforeach





