{{--Check if the channel has multiple channels--}}
<?php $hasMultipleChannels = count($data) > 1; ?>

@if($hasMultipleChannels)

    <?php
    $channelsTypes = array_column($data, 'channelTypeLabel');
    $defaultIndex = array_search('Algemeen', $channelsTypes);
    $bookingIndex = array_search('Na afspraak', $channelsTypes);
    $default = $defaultIndex !== false ? $data[$defaultIndex] : null;
    $booking = $bookingIndex !== false ? $data[$bookingIndex] : null;
    ?>

    @if($default && ($default['openinghours'][0]->open || !$booking || !$booking['openinghours'][0]->open))
        @foreach($default['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info_short', ['dayInfoObj' => $dayInfoObj, 'type' => $default['channelTypeLabel'],])
        @endforeach
    @endif

    @if($booking)
        @foreach($booking['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info_short', ['dayInfoObj' => $dayInfoObj, 'type' => $booking['channelTypeLabel'],])
        @endforeach
    @endif

@else
    @foreach($data as $channelData)
        {{--Looping over all openinghours objects--}}
        @foreach($channelData['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info_short', ['dayInfoObj' => $dayInfoObj])
        @endforeach
    @endforeach
@endif
