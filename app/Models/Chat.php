<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = [
        'user_id', 'target_id',
    ];
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function target() {
        return $this->belongsTo(User::class, 'target_id', 'id');
    }
}
