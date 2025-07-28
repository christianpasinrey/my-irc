<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class IrcService
{
    protected $server;
    protected $port;
    protected $nickname;
    protected $socket;
    protected $isConnected = false;
    protected $channels = [];
    protected $lastPing = 0;

    public function __construct(string $server, int $port, string $nickname = 'PHPBot')
    {
        $this->server = $server;
        $this->port = $port;
        $this->nickname = $nickname;
    }

    public function connect(): bool
    {
        try {
            $this->socket = fsockopen($this->server, $this->port, $errno, $errstr, 10);

            if (!$this->socket) {
                Log::error("IRC connection error $errno: $errstr");
                return false;
            }

            // Configurar timeout para lecturas no bloqueantes
            stream_set_timeout($this->socket, 1);
            stream_set_blocking($this->socket, false);

            // Registrar usuario
            $this->send("NICK {$this->nickname}");
            $this->send("USER {$this->nickname} 0 * :{$this->nickname}");

            // Esperar confirmación de conexión
            $timeout = time() + 10;
            while (time() < $timeout) {
                $data = $this->readLine();
                if ($data) {
                    Log::info("IRC << $data");

                    // Responder a PING
                    if (strpos($data, 'PING') === 0) {
                        $this->send('PONG ' . substr($data, 5));
                    }

                    // Verificar si estamos conectados
                    if (strpos($data, '001') !== false || strpos($data, '004') !== false) {
                        $this->isConnected = true;
                        Log::info("Successfully connected to IRC server");
                        return true;
                    }

                    // Verificar errores de conexión
                    if (strpos($data, '433') !== false) { // Nickname in use
                        $this->nickname .= '_';
                        $this->send("NICK {$this->nickname}");
                    }
                }
                usleep(100000); // 0.1 segundos
            }

            return $this->isConnected;
        } catch (\Exception $e) {
            Log::error("IRC Connection Exception: " . $e->getMessage());
            return false;
        }
    }

    public function isConnected(): bool
    {
        return $this->isConnected && $this->socket && !feof($this->socket);
    }

    public function joinChannel(string $channel): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        if (!str_starts_with($channel, '#')) {
            $channel = '#' . $channel;
        }

        $this->send("JOIN $channel");
        $this->channels[] = $channel;

        return true;
    }

    public function leaveChannel(string $channel): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $this->send("PART $channel");
        $this->channels = array_filter($this->channels, fn($c) => $c !== $channel);

        return true;
    }

    public function sendMessage(string $channel, string $message): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        if (!str_starts_with($channel, '#')) {
            $channel = '#' . $channel;
        }

        $this->send("PRIVMSG $channel :$message");
        return true;
    }

    public function getChannelList(): array
    {
        if (!$this->isConnected()) {
            return [];
        }

        $this->send("LIST");
        $channels = [];
        $timeout = time() + 5;

        while (time() < $timeout) {
            $data = $this->readLine();
            if ($data) {
                // 322 es el código para LIST response
                if (preg_match('/^:\S+ 322 \S+ (#\S+) (\d+) :(.*)$/', $data, $matches)) {
                    $channels[] = [
                        'name' => $matches[1],
                        'users' => (int)$matches[2],
                        'topic' => $matches[3]
                    ];
                }
                // 323 es el código para final de LIST
                if (strpos($data, '323') !== false) {
                    break;
                }
            }
            usleep(100000);
        }

        return $channels;
    }

    public function getChannelUsers(string $channel): array
    {
        if (!$this->isConnected()) {
            return [];
        }

        $this->send("NAMES $channel");
        $users = [];
        $timeout = time() + 3;

        while (time() < $timeout) {
            $data = $this->readLine();
            if ($data) {
                // 353 es el código para NAMES response
                if (preg_match('/^:\S+ 353 \S+ . ' . preg_quote($channel, '/') . ' :(.*)$/', $data, $matches)) {
                    $userList = explode(' ', trim($matches[1]));
                    foreach ($userList as $user) {
                        $user = trim($user);
                        if ($user) {
                            // Remover prefijos de operador (@, +, etc.)
                            $cleanUser = ltrim($user, '@+%&~');
                            if ($cleanUser) {
                                $users[] = $cleanUser;
                            }
                        }
                    }
                }
                // 366 es el código para final de NAMES
                if (strpos($data, '366') !== false) {
                    break;
                }
            }
            usleep(100000);
        }

        return array_unique($users);
    }

    public function readMessages(): array
    {
        $messages = [];

        if (!$this->isConnected()) {
            return $messages;
        }

        // Leer múltiples líneas disponibles
        for ($i = 0; $i < 10; $i++) {
            $data = $this->readLine();
            if (!$data) break;

            Log::info("IRC << $data");

            // Responder a PING
            if (strpos($data, 'PING') === 0) {
                $this->send('PONG ' . substr($data, 5));
                continue;
            }

            // Parsear mensajes PRIVMSG
            if (preg_match('/^:(\S+)!\S+ PRIVMSG (#\S+) :(.*)$/', $data, $matches)) {
                $messages[] = [
                    'nickname' => $matches[1],
                    'channel' => $matches[2],
                    'message' => $matches[3],
                    'timestamp' => now()->toISOString()
                ];
            }

            // Parsear otros eventos importantes
            if (preg_match('/^:\S+ (JOIN|PART|QUIT) (.*)$/', $data, $matches)) {
                // Eventos de usuario uniéndose/saliendo
                Log::info("User event: " . $data);
            }
        }

        return $messages;
    }

    protected function readLine(): ?string
    {
        if (!$this->socket || feof($this->socket)) {
            $this->isConnected = false;
            return null;
        }

        $data = fgets($this->socket, 512);
        return $data ? trim($data) : null;
    }

    protected function send(string $command): bool
    {
        if (!$this->socket || feof($this->socket)) {
            $this->isConnected = false;
            return false;
        }

        Log::info("IRC >> $command");
        $result = fputs($this->socket, $command . "\r\n");
        return $result !== false;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getJoinedChannels(): array
    {
        return $this->channels;
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            foreach ($this->channels as $channel) {
                $this->send("PART $channel");
            }
            $this->send("QUIT :Goodbye");
            fclose($this->socket);
        }

        $this->isConnected = false;
        $this->channels = [];
        $this->socket = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Escucha mensajes del socket IRC y ejecuta el callback por cada línea recibida.
     */
    public function listen(callable $onMessage)
    {
        $workerKey = "irc_worker_active_{$this->server}_{$this->nickname}";
        while ($this->isConnected()) {
            // Si la clave en cache desaparece, desconectar y terminar
            if (!\Illuminate\Support\Facades\Cache::has($workerKey)) {
                $this->disconnect();
                break;
            }
            $data = $this->readLine();
            if ($data === null) {
                usleep(100000); // Espera 0.1s si no hay datos
                continue;
            }
            // Responder a PING
            if (strpos($data, 'PING') === 0) {
                $this->send('PONG ' . substr($data, 5));
            }
            $onMessage($data);
        }
    }
}
