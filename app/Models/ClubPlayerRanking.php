<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubPlayerRanking extends Model
{
    protected $fillable = [
        'club_id',
        'player_id',
        'club_position',
        'club_elo',
        'ranking_matches_at_club',
        'ranking_wins_at_club',
        'ranking_losses_at_club',
        'win_rate_at_club',
        'last_computed_at',
    ];

    protected $casts = [
        'last_computed_at'  => 'datetime',
        'win_rate_at_club'  => 'float',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
