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
        'duration_minutes'                 
    ];    

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function owner()
    {
        return $this->belongsTo(Player::class, 'owner_player_id');
    }
    
    public function players()
    {
        return $this->belongsToMany(Player::class, 'game_players')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }
}