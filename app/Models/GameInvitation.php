<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'invited_by',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'game_id'    => 'integer',
        'player_id'  => 'integer',
        'invited_by' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function invitedBy()
    {
        return $this->belongsTo(Player::class, 'invited_by');
    }
}
