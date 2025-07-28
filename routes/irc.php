<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IRCServerController;
use App\Http\Controllers\IRCChatController;

// Rutas para gestión de servidores IRC
Route::prefix('irc')->middleware(['auth'])->group(function () {
    // Gestión de servidores IRC
    Route::get('/servers', [IRCServerController::class, 'index'])->name('irc-servers.index');
    Route::get('/servers/create', [IRCServerController::class, 'create'])->name('irc-servers.create');
    Route::post('/servers', [IRCServerController::class, 'store'])->name('irc-servers.store');
    Route::get('/servers/{id}/edit', [IRCServerController::class, 'edit'])->name('irc-servers.edit');
    Route::put('/servers/{id}', [IRCServerController::class, 'update'])->name('irc-servers.update');
    Route::delete('/servers/{id}', [IRCServerController::class, 'destroy'])->name('irc-servers.destroy');

    // Rutas para el chat IRC
    Route::get('/chat/{server}', [IRCChatController::class, 'show'])->name('irc-chat.show');
    Route::post('/chat/{server}/connect', [IRCChatController::class, 'connect'])->name('irc-chat.connect');
    Route::post('/chat/{server}/disconnect', [IRCChatController::class, 'disconnect'])->name('irc-chat.disconnect');
    Route::post('/chat/{server}/send', [IRCChatController::class, 'sendMessage'])->name('irc-chat.send');
    Route::get('/chat/{server}/messages', [IRCChatController::class, 'getMessages'])->name('irc-chat.messages');
    Route::post('/chat/{server}/join-channel', [IRCChatController::class, 'joinChannel'])->name('irc-chat.join-channel');
    Route::post('/chat/{server}/leave-channel', [IRCChatController::class, 'leaveChannel'])->name('irc-chat.leave-channel');

    // Nuevas rutas para funcionalidad extendida
    Route::get('/chat/{server}/channel-list', [IRCChatController::class, 'getChannelList'])->name('irc-chat.channel-list');
    Route::get('/chat/{server}/channel-users', [IRCChatController::class, 'getChannelUsers'])->name('irc-chat.channel-users');
    Route::get('/chat/{server}/status', [IRCChatController::class, 'getConnectionStatus'])->name('irc-chat.status');

    // Ruta para desconectar todas las conexiones del usuario
    Route::post('/chat/disconnect-all', [IRCChatController::class, 'disconnectAll'])->name('irc-chat.disconnect-all');
});

// API para obtener lista de servidores (fuera del middleware de auth para AJAX)
Route::get('/irc/api/servers', [IRCServerController::class, 'list'])->middleware('auth')->name('irc-servers.list');
