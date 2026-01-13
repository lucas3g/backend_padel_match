<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
            "full_name" => 'required|string|max:255',
            "phone"=> 'nullable|string|max:20',
            "level"=> 'required|integer',
            "side"=> 'required|in:left,right,both',
            "bio"=> 'nullable|string|max:2500',
            "profile_image_url"=> 'nullable',
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
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
            "full_name" => 'required|string|max:255',
            "phone"=> 'nullable|string|max:20',
            "level"=> 'required|integer',
            "side"=> 'required|in:left,right,both',
            "bio"=> 'nullable|string|max:2500',
            "profile_image_url"=> 'nullable',
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
        ]);

        $player->update($data);

        return response()->json($player);
    }
}
