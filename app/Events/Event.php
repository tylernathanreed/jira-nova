<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

abstract class Event
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Creates and returns the specified private channel.
     *
     * @return \Illuminate\Broadcasting\PrivateChannel
     */
    public function newPrivateChannel($name)
    {
        return new PrivateChannel($name);
    }
}
