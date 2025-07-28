<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class IRCMessageReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $serverId;
    public $channel;
    public $nickname;
    public $message;
    public $timestamp;

    public function __construct($serverId, $channel, $nickname, $message, $timestamp)
    {
        $this->serverId = $serverId;
        $this->channel = $channel;
        $this->nickname = $nickname;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public function broadcastOn()
    {
        return new Channel('irc.' . $this->serverId . '.' . ltrim($this->channel, '#'));
    }

    public function broadcastWith()
    {
        return [
            'serverId' => $this->serverId,
            'channel' => $this->channel,
            'nickname' => $this->nickname,
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];
    }
}
