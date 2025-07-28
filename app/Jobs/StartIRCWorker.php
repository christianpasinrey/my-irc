<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class StartIRCWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $serverId, $host, $port, $nickname, $channel;

    public function __construct($serverId, $host, $port, $nickname, $channel)
    {
        $this->serverId = $serverId;
        $this->host = $host;
        $this->port = $port;
        $this->nickname = $nickname;
        $this->channel = $channel;
    }

    public function handle()
    {
        // Crear la clave en cache para que el worker permanezca activo
        $workerKey = "irc_worker_active_{$this->host}_{$this->nickname}";
        \Illuminate\Support\Facades\Cache::put($workerKey, true, 3600);

        $process = new Process([
            'php', 'artisan', 'irc:worker',
            $this->serverId,
            $this->host,
            $this->port,
            $this->nickname,
            $this->channel
        ]);
        $process->start();
    }
}
