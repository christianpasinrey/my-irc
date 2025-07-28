<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\IRCServer;
use App\Models\IRCMessage;
use App\Services\IrcService;
use App\Services\IrcConnectionManager;

class IRCChatController extends Controller
{
    public function show(IRCServer $server)
    {
        $user = Auth::user();
        $connectionInfo = IrcConnectionManager::getConnectionInfo($server->id, $user->id);

        return Inertia::render('IRC/Chat', [
            'server' => $server,
            'channels' => $connectionInfo ? [] : ['#general', '#random', '#help'], // Canales por defecto cuando no hay conexión
            'nickname' => $user->name ?? 'Usuario',
            'isConnected' => IrcConnectionManager::isConnected($server->id, $user->id),
            'connectionInfo' => $connectionInfo
        ]);
    }

    public function connect(Request $request, IRCServer $server)
    {
        $request->validate([
            'nickname' => 'required|string|max:50|alpha_dash',
            'channel' => 'required|string|max:50',
        ]);

        $user = Auth::user();

        try {
            // Lanzar el worker IRC en background usando el job
            \App\Jobs\StartIRCWorker::dispatch(
                $server->id,
                $server->host,
                $server->port,
                $request->nickname,
                $request->channel
            );
            // Crear nueva conexión
            $ircService = IrcConnectionManager::createConnection(
                $server->id,
                $user->id,
                $server->host,
                $server->port,
                $request->nickname
            );

            if (!$ircService) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar al servidor IRC. Verifique que el servidor esté disponible.'
                ], 500);
            }

            // Unirse al canal inicial
            if (!$ircService->joinChannel($request->channel)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conectado al servidor pero no se pudo unir al canal.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conectado exitosamente al servidor IRC',
                'server' => $server->name,
                'channel' => $request->channel,
                'nickname' => $ircService->getNickname()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disconnect(Request $request, IRCServer $server)
    {
        $user = Auth::user();
        // Lanzar el job para detener el worker IRC (con host)
        \App\Jobs\StopIRCWorker::dispatch($request->nickname, $request->channel, $server->host);
        IrcConnectionManager::closeConnection($server->id, $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Desconectado del servidor IRC'
        ]);
    }

    public function sendMessage(Request $request, IRCServer $server)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'channel' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);

        if (!$ircService) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        try {
            // Enviar mensaje al IRC real
            if (!$ircService->sendMessage($request->channel, $request->message)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar mensaje al servidor IRC'
                ], 500);
            }

            // Guardar mensaje en la base de datos
            IRCMessage::create([
                'server_id' => $server->id,
                'nickname' => $ircService->getNickname(),
                'message' => $request->message,
                'channel' => $request->channel,
                'timestamp' => now()
            ]);

            // Actualizar actividad
            IrcConnectionManager::updateActivity($server->id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'nickname' => $ircService->getNickname(),
                    'message' => $request->message,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar mensaje: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMessages(Request $request, IRCServer $server)
    {
        $user = Auth::user();
        $channel = $request->get('channel', '#general');
        $limit = $request->get('limit', 50);

        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);
        $liveMessages = [];

        // Obtener mensajes en tiempo real del IRC si hay conexión activa
        if ($ircService) {
            $liveMessages = $ircService->readMessages();

            // Guardar mensajes nuevos en la base de datos
            foreach ($liveMessages as $message) {
                if ($message['channel'] === $channel) {
                    IRCMessage::firstOrCreate([
                        'server_id' => $server->id,
                        'nickname' => $message['nickname'],
                        'message' => $message['message'],
                        'channel' => $message['channel'],
                        'timestamp' => $message['timestamp']
                    ]);
                }
            }

            IrcConnectionManager::updateActivity($server->id, $user->id);
        }

        // Obtener mensajes de la base de datos
        $dbMessages = IRCMessage::where('server_id', $server->id)
            ->where('channel', $channel)
            ->orderBy('timestamp', 'desc')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $dbMessages,
            'isConnected' => $ircService !== null
        ]);
    }

    public function joinChannel(Request $request, IRCServer $server)
    {
        $request->validate([
            'channel' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);

        if (!$ircService) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        $channel = $request->channel;
        if (!str_starts_with($channel, '#')) {
            $channel = '#' . $channel;
        }

        try {
            if ($ircService->joinChannel($channel)) {
                IrcConnectionManager::updateActivity($server->id, $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Unido al canal ' . $channel,
                    'channel' => $channel
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo unir al canal ' . $channel
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al unirse al canal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function leaveChannel(Request $request, IRCServer $server)
    {
        $request->validate([
            'channel' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);

        if (!$ircService) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        try {
            if ($ircService->leaveChannel($request->channel)) {
                IrcConnectionManager::updateActivity($server->id, $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Has salido del canal ' . $request->channel
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo salir del canal'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al salir del canal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getChannelList(Request $request, IRCServer $server)
    {
        $user = Auth::user();
        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);

        if (!$ircService) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        try {
            $channels = $ircService->getChannelList();
            IrcConnectionManager::updateActivity($server->id, $user->id);

            return response()->json([
                'success' => true,
                'channels' => $channels
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lista de canales: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getChannelUsers(Request $request, IRCServer $server)
    {
        $user = Auth::user();
        $channel = $request->get('channel', '#general');
        $ircService = IrcConnectionManager::getConnection($server->id, $user->id);

        if (!$ircService) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor',
                'users' => []
            ]);
        }

        try {
            $users = $ircService->getChannelUsers($channel);
            IrcConnectionManager::updateActivity($server->id, $user->id);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios del canal: ' . $e->getMessage(),
                'users' => []
            ]);
        }
    }

    public function getConnectionStatus(Request $request, IRCServer $server)
    {
        $user = Auth::user();
        $isConnected = IrcConnectionManager::isConnected($server->id, $user->id);
        $connectionInfo = IrcConnectionManager::getConnectionInfo($server->id, $user->id);

        return response()->json([
            'success' => true,
            'isConnected' => $isConnected,
            'connectionInfo' => $connectionInfo
        ]);
    }

    // Desconectar todas las conexiones IRC activas del usuario
    public function disconnectAll(Request $request)
    {
        $user = Auth::user();
        $count = \App\Services\IrcConnectionManager::disconnectAllForUser($user->id);
        return response()->json([
            'success' => true,
            'message' => "Desconectadas $count conexiones IRC y workers para el usuario."
        ]);
    }
}
