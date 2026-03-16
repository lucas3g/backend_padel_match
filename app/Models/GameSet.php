<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSet extends Model
{
    protected $fillable = [
        'game_id',
        'set_number',
        'team1_score',
        'team2_score',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
