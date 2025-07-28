<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IRCMessage extends Model
{
    protected $fillable = ['server_id', 'nickname', 'message', 'channel', 'timestamp'];

    public function server()
    {
        return $this->belongsTo(IRCServer::class, 'server_id');
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('timestamp', 'desc')->take($limit);
    }
}
