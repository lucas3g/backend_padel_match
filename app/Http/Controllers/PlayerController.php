<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->player
        );
    }

    public function store(Request $request)
    {
        // evita duplicidade
        if ($request->user()->player) {
            return response()->json([
                'message' => 'Usuário já possui um player cadastrado'
            ], 422);
        }

        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'data_nascimento' => 'nullable|date',
            'posicao' => 'nullable|string|max:100',
        ]);

        $player = $request->user()->player()->create($data);

        return response()->json($player, 201);
    }

    public function update(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Player não encontrado'
            ], 404);
        }

        $data = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'data_nascimento' => 'sometimes|date',
            'posicao' => 'sometimes|string|max:100',
        ]);

        $player->update($data);

        return response()->json($player);
    }
}
