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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
