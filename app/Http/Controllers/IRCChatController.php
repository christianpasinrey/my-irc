<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\IRCServer;
use App\Models\IRCMessage;
use App\Services\IrcService;

class IRCChatController extends Controller
{
    protected $activeConnections = [];

    public function show(IRCServer $server)
    {
        return Inertia::render('IRC/Chat', [
            'server' => $server,
            'channels' => ['#general', '#random', '#help'], // Canales por defecto
            'nickname' => Auth::user()->name ?? 'Usuario'
        ]);
    }

    public function connect(Request $request, IRCServer $server)
    {
        $request->validate([
            'nickname' => 'required|string|max:50',
            'channel' => 'required|string|max:50',
        ]);

        try {
            $ircService = IRCServer::connect(
                $server->host,
                $server->port,
                $request->nickname,
                $request->channel
            );

            if ($ircService) {
                // Almacenar la conexión en sesión o cache
                session(['irc_connection_' . $server->id => [
                    'nickname' => $request->nickname,
                    'channel' => $request->channel,
                    'connected' => true
                ]]);

                return response()->json([
                    'success' => true,
                    'message' => 'Conectado exitosamente al servidor IRC',
                    'server' => $server->name,
                    'channel' => $request->channel
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo conectar al servidor IRC'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disconnect(Request $request, IRCServer $server)
    {
        // Remover la conexión de la sesión
        session()->forget('irc_connection_' . $server->id);

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

        $connection = session('irc_connection_' . $server->id);

        if (!$connection || !$connection['connected']) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        try {
            // Aquí integrarías con el servicio IRC real
            // Por ahora, solo guardamos el mensaje en la base de datos
            IRCMessage::create([
                'server_id' => $server->id,
                'nickname' => $connection['nickname'],
                'message' => $request->message,
                'channel' => $request->channel,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado',
                'data' => [
                    'nickname' => $connection['nickname'],
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
        $channel = $request->get('channel', '#general');
        $limit = $request->get('limit', 50);

        $messages = IRCMessage::where('server_id', $server->id)
            ->where('channel', $channel)
            ->orderBy('timestamp', 'desc')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function joinChannel(Request $request, IRCServer $server)
    {
        $request->validate([
            'channel' => 'required|string|max:50',
        ]);

        $connection = session('irc_connection_' . $server->id);

        if (!$connection || !$connection['connected']) {
            return response()->json([
                'success' => false,
                'message' => 'No hay conexión activa con el servidor'
            ], 400);
        }

        // Actualizar el canal actual en la sesión
        $connection['channel'] = $request->channel;
        session(['irc_connection_' . $server->id => $connection]);

        return response()->json([
            'success' => true,
            'message' => 'Unido al canal ' . $request->channel,
            'channel' => $request->channel
        ]);
    }

    public function leaveChannel(Request $request, IRCServer $server)
    {
        $request->validate([
            'channel' => 'required|string|max:50',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Has salido del canal ' . $request->channel
        ]);
    }
}
