<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StopIRCWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $nickname, $channel, $host;

    public function __construct($nickname, $channel)
    {
        $this->nickname = $nickname;
        $this->channel = $channel;
        $this->host = func_num_args() > 2 ? func_get_arg(2) : null;
    }

    public function handle()
    {
        // Elimina la clave en cache que el worker vigila para forzar la desconexión
        if ($this->host) {
            $workerKey = "irc_worker_active_{$this->host}_{$this->nickname}";
            \Illuminate\Support\Facades\Cache::forget($workerKey);
        }
    }
}
