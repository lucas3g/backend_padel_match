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
        'type',
        'status',
        'data_time',
        'club_id',
        'court_id',
        'custom_location',
        'min_level',
        'max_level',        
        'max_players',
        'price',
        'cost_per_player',
        'team1_score',
        'team2_score',
        'game_type',
        'winner_team',
        'creator_id',
        'duration_minutes'                 
    ];    
    
    public function players()
    {
        return $this->belongsToMany(Player::class, 'game_players')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }
}