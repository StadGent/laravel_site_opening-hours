{{--    Only channels of type 'Algemeen' and 'Na afspraak' are shown.
        If there are more than one channels the order matters.  --}}
<?php
$channelsTypes = array_column($data, 'channelTypeLabel');
$defaultIndex = array_search('Algemeen', $channelsTypes);
$bookingIndex = array_search('Na afspraak', $channelsTypes);
$default = $defaultIndex !== false ? $data[$defaultIndex] : null;
$booking = $bookingIndex !== false ? $data[$bookingIndex] : null;
?>
@if(!$hasOneChannel)
    @if($default && ($default['openinghours'][0]->open || !$booking || !$booking['openinghours'][0]->open))
        @foreach($default['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info', [
            'dayInfoObj' => $dayInfoObj,
            'type' => $default['channelTypeLabel'],
            'short' => true])
        @endforeach
    @endif

    @if($booking)
        @foreach($booking['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info', [
            'dayInfoObj' => $dayInfoObj,
            'type' => $booking['channelTypeLabel'],
            'short' => true])
        @endforeach
    @endif

@else
    @foreach($data as $channelData)
        @foreach($channelData['openinghours'] as $dayInfoObj)
            @include('api.openinghours.day_info', ['dayInfoObj' => $dayInfoObj, 'short' => true])
        @endforeach
    @endforeach
@endif
