<?php

namespace App\Services;

class IrcService
{
    protected $server;
    protected $port;
    protected $nickname;
    protected $channel;
    protected $socket;

    public function __construct(string $server, int $port, string $nickname, string $channel)
    {
        $this->server = $server;
        $this->port = $port;
        $this->nickname = $nickname;
        $this->channel = $channel;
    }

    public function connect(): bool
    {
        $this->socket = fsockopen($this->server, $this->port, $errno, $errstr, 30);

        if (!$this->socket) {
            logger("IRC connection error $errno: $errstr");
            return false;
        }

        $this->send("NICK {$this->nickname}");
        $this->send("USER {$this->nickname} 0 * :{$this->nickname}");
        $this->send("JOIN {$this->channel}");

        return true;
    }

    public function listen(callable $onMessage)
    {
        while (!feof($this->socket)) {
            $data = fgets($this->socket, 512);
            if ($data === false) continue;

            $data = trim($data);
            logger("IRC >> $data");

            if (strpos($data, 'PING') === 0) {
                $this->send('PONG ' . substr($data, 5));
            }

            $onMessage($data);
        }
    }

    public function sendMessage(string $message)
    {
        $this->send("PRIVMSG {$this->channel} :$message");
    }

    protected function send(string $command)
    {
        fputs($this->socket, $command . "\r\n");
    }

    public function disconnect()
    {
        fclose($this->socket);
    }
}
