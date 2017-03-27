<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;

class ChannelDeleted
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array $channel The entire channel object
     * @return void
     */
    public function __construct(array $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Return the ID of the updated channel object
     *
     * @return int
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
