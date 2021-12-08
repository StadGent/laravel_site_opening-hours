{{--Check if the channel has multiple channels--}}
<?php $hasMultipleChannels = count($data) > 1; ?>
@foreach($data as $channelData)
    {{--If multiple channels are present the channel name is printed--}}
    @if($hasMultipleChannels)
        <div class="channel-label">{{ $channelData['channel'] }}</div>
    @endif
    <div vocab="http://schema.org/"
         class="openinghours openinghours--list">
        <ul class="openinghours--days">
            @foreach($channelData['openinghours'] as $dayInfoObj)
                @php
                    $isSameDay = (new Carbon\Carbon())->isSameDay($dayInfoObj->date);
                    $status = empty($dayInfoObj->hours) ? 'closed' : 'open';
                    $classList = 'openinghours--day openinghours--day-'. $status;
                    if ($isSameDay) {
                        $classList .= ' openinghours--day-active';
                    }
                @endphp
                <li class="{{ $classList }}">
                    @include('api.openinghours.day_info', [
                        'dayInfoObj' => $dayInfoObj,
                        'includeRFDa' => $channelData['channelTypeLabel'] == 'Algemeen'
                        ])
                </li>
            @endforeach
        </ul>
    </div>
@endforeach





