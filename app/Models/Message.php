<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $hidden = [
        'updated_at',
    ];

    protected $fillable = [
        'sender',
        'receiver',
        'text',
        'id',
    ];

}
