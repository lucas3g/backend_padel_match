<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'game_type',
        'status',
        'court_id',
        'custom_location',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'min_level',
        'max_level',
        'max_players',
        'current_players',
        'cost_per_player',
        'payment_required',
        'team1_score',
        'team2_score',
        'winner_team',
        'completed_at',
        'creator_id'            
    ];    
}
