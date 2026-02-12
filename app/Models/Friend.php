<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'friend_id',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function friend()
    {
        return $this->belongsTo(Player::class, 'friend_id');
    }
}
