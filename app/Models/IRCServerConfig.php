<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IRCServerConfig extends Model
{
    protected $fillable = ['server_id', 'key', 'value'];

    public function server()
    {
        return $this->belongsTo(IRCServer::class, 'server_id');
    }
}
