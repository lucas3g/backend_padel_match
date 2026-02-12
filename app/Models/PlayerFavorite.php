<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'favorite_player_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function favoritePlayer()
    {
        return $this->belongsTo(Player::class, 'favorite_player_id');
    }
}
