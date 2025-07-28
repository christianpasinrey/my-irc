<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IrcConnectionManager
{
    protected static $connections = [];

    public static function getConnection(int $serverId, int $userId): ?IrcService
    {
        $key = "irc_connection_{$serverId}_{$userId}";

        if (isset(self::$connections[$key])) {
            $connection = self::$connections[$key];
            if ($connection->isConnected()) {
                return $connection;
            } else {
                // Conexión perdida, limpiar
                unset(self::$connections[$key]);
                Cache::forget($key);
            }
        }

        return null;
    }

    public static function createConnection(
        int $serverId,
        int $userId,
        string $host,
        int $port,
        string $nickname
    ): ?IrcService {
        $key = "irc_connection_{$serverId}_{$userId}";

        // Si el worker ya está activo para este usuario/servidor, no crear nueva conexión
        $workerKey = "irc_worker_active_{$serverId}_{$nickname}";
        if (Cache::get($workerKey)) {
            // Retornar un proxy que solo interactúa con eventos
            return new class($nickname) extends IrcService {
                public function __construct(string $nickname) { parent::__construct('', 0, $nickname); }
                public function connect(): bool { return true; }
                public function isConnected(): bool { return true; }
                public function joinChannel(string $channel): bool { return true; }
                public function leaveChannel(string $channel): bool { return true; }
                public function sendMessage(string $channel, string $message): bool {
                    event(new \App\Events\IRCMessageSendRequest($this->nickname, $channel, $message));
                    return true;
                }
                public function readMessages(): array { return []; }
                public function getChannelList(): array { return []; }
                public function getChannelUsers(string $channel): array { return []; }
            };
        }

        // Si no hay worker, crear conexión real
        self::closeConnection($serverId, $userId);

        $ircService = new IrcService($host, $port, $nickname);

        if ($ircService->connect()) {
            self::$connections[$key] = $ircService;

            // Guardar info de conexión en cache
            Cache::put($key, [
                'server_id' => $serverId,
                'user_id' => $userId,
                'nickname' => $ircService->getNickname(),
                'connected_at' => now(),
                'last_activity' => now()
            ], 3600); // 1 hora

            Log::info("IRC connection established for user $userId to server $serverId");
            return $ircService;
        }

        return null;
    }

    public static function closeConnection(int $serverId, int $userId): void
    {
        $key = "irc_connection_{$serverId}_{$userId}";

        if (isset(self::$connections[$key])) {
            self::$connections[$key]->disconnect();
            unset(self::$connections[$key]);
        }

        Cache::forget($key);
        Log::info("IRC connection closed for user $userId to server $serverId");
    }

    public static function isConnected(int $serverId, int $userId): bool
    {
        $connection = self::getConnection($serverId, $userId);
        return $connection !== null && $connection->isConnected();
    }

    public static function updateActivity(int $serverId, int $userId): void
    {
        $key = "irc_connection_{$serverId}_{$userId}";
        $info = Cache::get($key);

        if ($info) {
            $info['last_activity'] = now();
            Cache::put($key, $info, 3600);
        }
    }

    public static function getConnectionInfo(int $serverId, int $userId): ?array
    {
        $key = "irc_connection_{$serverId}_{$userId}";
        return Cache::get($key);
    }

    public static function cleanupInactiveConnections(): void
    {
        foreach (self::$connections as $key => $connection) {
            if (!$connection->isConnected()) {
                unset(self::$connections[$key]);
                Cache::forget($key);
            }
        }
    }

    public static function getAllActiveConnections(): array
    {
        $active = [];
        // Compatible con driver database: consultar la tabla cache
        try {
            $rows = DB::table('cache')->where('key', 'like', 'irc_connection_%')->get();
            foreach ($rows as $row) {
                $info = @unserialize($row->value);
                if ($info && is_array($info) && isset($info['user_id'])) {
                    $active[] = $info;
                }
            }
        } catch (\Exception $e) {
            // Si no existe la tabla o el driver no es database, retorna solo las conexiones locales
            foreach (self::$connections as $key => $connection) {
                if ($connection->isConnected()) {
                    $info = Cache::get($key);
                    if ($info) {
                        $active[] = $info;
                    }
                }
            }
        }
        return $active;
    }

    // Elimina todas las conexiones y workers activos del usuario en Cache (driver database)
    public static function disconnectAllForUser(int $userId): int
    {
        $count = 0;
        // Eliminar conexiones IRC
        try {
            $rows = DB::table('cache')->where('key', 'like', 'irc_connection_%')->get();
            foreach ($rows as $row) {
                $info = @unserialize($row->value);
                if ($info && is_array($info) && isset($info['user_id']) && $info['user_id'] == $userId) {
                    Cache::forget($row->key);
                    $count++;
                }
            }
        } catch (\Exception $e) {
            // Fallback: solo conexiones locales
            foreach (self::$connections as $key => $connection) {
                $info = Cache::get($key);
                if ($info && $info['user_id'] == $userId) {
                    self::closeConnection($info['server_id'], $userId);
                    $count++;
                }
            }
        }
        // Eliminar workers activos
        try {
            $rows = DB::table('cache')->where('key', 'like', 'irc_worker_active_%')->get();
            foreach ($rows as $row) {
                Cache::forget($row->key);
            }
        } catch (\Exception $e) {}
        return $count;
    }
}
