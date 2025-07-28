<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\IrcService;

class IRCServer extends Model
{
    protected $fillable = ['name', 'host', 'port'];

    public function configs()
    {
        return $this->hasMany(IRCServerConfig::class, 'server_id');
    }

    public function messages()
    {
        return $this->hasMany(IRCMessage::class, 'server_id');
    }

    public static function connect(string $host, int $port, string $nickname, string $channel): ?IrcService
    {
        $ircService = new IrcService($host, $port, $nickname, $channel);

        if ($ircService->connect()) {
            return $ircService;
        }

        return null;
    }
}
