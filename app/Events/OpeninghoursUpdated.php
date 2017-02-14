<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;

class OpeninghoursUpdated
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The ID of the updated openinghours object
     * @var integer
     */
    private $openinghoursId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $openinghoursId)
    {
        $this->openinghoursId = $openinghoursId;
    }

    /**
     * Return the ID of the updated openinghours object
     *
     * @return int
     */
    public function getOpeninghoursId()
    {
        return $this->openinghoursId;
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
