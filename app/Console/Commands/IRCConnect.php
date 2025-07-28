<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IrcService;

class IRCConnect extends Command
{
    protected $signature = 'irc:connect';
    protected $description = 'Conectar al servidor IRC y escuchar mensajes';

    public function handle()
    {
        $server = 'irc.chathispano.com';
        $port = 6667;
        $nickname = 'laravel_dev';
        $channel = '#irchispano';

        $irc = new IrcService($server, $port, $nickname, $channel);

        if (!$irc->connect()) {
            $this->error("No se pudo conectar al IRC.");
            return;
        }

        $this->info("Conectado a $server como $nickname en $channel");

        $irc->listen(function ($message) use ($irc) {
            if (str_contains($message, 'hola')) {
                $irc->sendMessage("Hola! Soy Laravel 😎");
            }
        });

        $irc->disconnect();
    }
}
