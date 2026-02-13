<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'level',
        'side',
        'bio',
        'profile_image_url',
        'total_matches',
        'wins',
        'losses',
        'ranking_points',
        'ranking_position',
        'preferred_locations',
        'data_nascimento',
        'posicao',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_players')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function stats()
    {
        return $this->hasOne(PlayerStat::class);
    }

    public function ownedGames()
    {
        return $this->hasMany(Game::class, 'owner_player_id');
    }

    public function friendshipsInitiated()
    {
        return $this->hasMany(Friend::class, 'player_id');
    }

    public function friendshipsReceived()
    {
        return $this->hasMany(Friend::class, 'friend_id');
    }

    public function favorites()
    {
        return $this->belongsToMany(Player::class, 'player_favorites', 'player_id', 'favorite_player_id')
            ->withTimestamps();
    }

    public function gameInvitations()
    {
        return $this->hasMany(GameInvitation::class, 'player_id');
    }
}
