<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IRCServer extends Model
{
    protected $fillable = ['name', 'host', 'port'];

    public function messages()
    {
        return $this->hasMany(IRCMessage::class, 'server_id');
    }
}
