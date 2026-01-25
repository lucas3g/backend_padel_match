<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'total_matches',
        'wins',
        'losses',
        'win_rate',
        'current_streak',
        'longest_streak',
        'average_elo'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
