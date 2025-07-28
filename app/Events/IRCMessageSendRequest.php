<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IRCMessageSendRequest
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $nickname;
    public $channel;
    public $message;

    public function __construct(string $nickname, string $channel, string $message)
    {
        $this->nickname = $nickname;
        $this->channel = $channel;
        $this->message = $message;
    }
}
