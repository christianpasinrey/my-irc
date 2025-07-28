<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class IRCServerController extends Controller
{
    public function index()
    {
        return Inertia::render('IRC/Index');
    }

    public function create()
    {
        return Inertia::render('IRC/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        $server = new \App\Models\IRCServer();
        $server->fill($request->all());
        $server->save();

        return redirect()->route('irc-servers.index')->with('success', 'Servidor IRC creado exitosamente.');
    }

    public function edit($id)
    {
        $server = \App\Models\IRCServer::findOrFail($id);
        return Inertia::render('IRC/Edit', ['server' => $server]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        $server = \App\Models\IRCServer::findOrFail($id);
        $server->fill($request->all());
        $server->save();

        return redirect()->route('irc-servers.index')->with('success', 'Servidor IRC actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $server = \App\Models\IRCServer::findOrFail($id);
        $server->delete();

        return redirect()->route('irc-servers.index')->with('success', 'Servidor IRC eliminado exitosamente.');
    }

    public function list()
    {
        $servers = \App\Models\IRCServer::all();
        return response()->json($servers);
    }
}
