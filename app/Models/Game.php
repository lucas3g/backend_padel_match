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

    public function invitations()
    {
        return $this->hasMany(GameInvitation::class);
    }

    public function invitedPlayers()
    {
        return $this->belongsToMany(Player::class, 'game_invitations')
                    ->withPivot('status', 'invited_by')
                    ->withTimestamps();
    }

    public function isOwner(Player $player): bool
    {
        return $this->owner_player_id === $player->id;
    }

    public function isInvited(Player $player): bool
    {
        return $this->invitations()
            ->where('player_id', $player->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();
    }

    public function isInvitedAccepted(Player $player): bool
    {
        return $this->invitations()
            ->where('player_id', $player->id)
            ->where('status', 'accepted')
            ->exists();
    }

    public function canAccess(Player $player): bool
    {
        if ($this->type === 'public') {
            return true;
        }

        return $this->isOwner($player)
            || $this->isInvited($player)
            || $this->players()->where('players.id', $player->id)->exists();
    }
}