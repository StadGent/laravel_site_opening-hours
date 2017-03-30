<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;

class OpeninghoursDeleted
{
    use InteractsWithSockets, SerializesModels;

   /**
     * The ID of the updated openinghours object
     * @var integer
     */
    private $openinghours;

    /**
     * Create a new event instance.
     *
     * @param  array $openinghours The entire openinghours object
     * @return void
     */
    public function __construct(array $openinghours)
    {
        $this->openinghours = $openinghours;
    }

    /**
     * Return the ID of the updated openinghours object
     *
     * @return int
     */
    public function getOpeninghours()
    {
        return $this->openinghours;
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
