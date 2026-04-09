<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'ranking_position',
        'average_elo',
        'active_players',
        'total_ranking_games',
        'win_rate',
        'last_computed_at',
    ];

    protected $casts = [
        'last_computed_at' => 'datetime',
        'average_elo'      => 'float',
        'win_rate'         => 'float',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
