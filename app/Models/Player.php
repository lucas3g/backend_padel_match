<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $appends = ['municipio_descricao'];

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
        'uf',
        'municipio_ibge',
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

    protected $casts = [
        'preferred_locations' => 'array',
    ];

    public function getMunicipioDescricaoAttribute(): ?string
    {
        return $this->municipio?->descricao;
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_ibge', 'codigo_ibge');
    }

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
