<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;

class CalendarUpdated
{
    use InteractsWithSockets, SerializesModels;

    /**
     * The ID of the updated calendar
     * @var integer
     */
    private $calendarId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($calendarId)
    {
        $this->calendarId = $calendarId;
    }

    /**
     * Return the ID of the updated calendar
     *
     * @return integer
     */
    public function getCalendarId()
    {
        return $this->calendarId;
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
