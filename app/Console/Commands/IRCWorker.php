<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IrcService;
use App\Events\IRCMessageReceived;

class IRCWorker extends Command
{
    protected $signature = 'irc:worker {server_id} {host} {port} {nickname} {channel}';
    protected $description = 'Inicia un worker IRC que emite mensajes por Reverb';

    public function handle()
    {
        $serverId = $this->argument('server_id');
        $host = $this->argument('host');
        $port = $this->argument('port');
        $nickname = $this->argument('nickname');
        $channel = $this->argument('channel');

        $irc = new IrcService($host, $port, $nickname, $channel);
        if (!$irc->connect()) {
            $this->error('No se pudo conectar al servidor IRC');
            return 1;
        }
        $irc->joinChannel($channel);
        $this->info("Conectado a $host:$port como $nickname en $channel");

        $irc->listen(function($raw) use ($serverId, $channel) {
            // Solo emitir mensajes PRIVMSG
            if (preg_match('/^:(\S+)!\S+ PRIVMSG (#\S+) :(.*)$/', $raw, $matches)) {
                $nickname = $matches[1];
                $chan = $matches[2];
                $message = $matches[3];
                event(new IRCMessageReceived($serverId, $chan, $nickname, $message, now()->toISOString()));
                $this->info("[$chan] <$nickname> $message");
            }
        });
    }
}
