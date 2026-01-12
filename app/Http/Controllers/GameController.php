<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->game
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'level' => 'required|integer',
            'side' => 'nullable|string|max:100',
        ],
        [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
        ]);        

        $game = $request->user()->game()->create($data);

        return response()->json($game, 201);
    }

    public function update(Request $request)
    {
        $game = $request->user()->game;

        if (!$game) {
            return response()->json([
                'message' => 'Jogo não encontrado'
            ], 404);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:25  5',
            'level' => 'required|integer',
            'side' => 'nullable|string|max:100',
        ],
        [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
        ]);

        $game->update($data);

        return response()->json($game);
    }
}
