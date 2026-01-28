<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GamePlayerController extends Controller
{
    public function store(Request $request, Game $game)
    {

        $player = $request->user()->player;

        $data = $request->validate([
            'players'   => 'required|array|min:1'
        ]);        

        //verificar limite de jogadores alterar o 4 pelo limite da partida
        if ($game->players()->count() + count($data['players']) > 4) {
            return response()->json([
                'message' => 'A partida permite no máximo 4 jogadores'
            ], 422);
        }

        //inser o novo player na partida
        $game->players()->syncWithoutDetaching([
            $player->id => ['joined_at' => now()]
        ]);

        return response()->json([
            'message' => 'Entrou na partida'
        ]);
    }
}
