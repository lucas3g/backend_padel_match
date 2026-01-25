<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            Game::all()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => 'nullable|string|max:255',
            "description"=> 'nullable|string|max:500',            
            "type"  => 'required|in:public,private',
            "data_time" => 'required',
            "club_id" => 'required',
            "court_id" => 'required',
            "custom_location" => 'nullable|string|max:500',
            "min_level" => 'nullable',
            "max_level" => 'nullable',
            "max_players" => 'nullable',
            "status"     => 'required|in:open,full,in_progress,completed, canceled',
            "price" => 'nullable',
            "cost_per_player" => 'nullable',
            "game_type" => 'required|in:casual, competitive, training',
            "duration_minutes" => 'nullable'            
        ], [
            'type.required' => 'O tipo de partida é obrigatório. Defina entre publica ou privada',
            'status.required' => 'A status da partida é obrigatório.',
            'data_time.required' => 'A data e hora da partida é obrigatório.',
            'club_id.required' => 'O clube é obrigatório.',
            'court_id.required' => 'A quadra é obrigatório.',
            'court_id.required' => 'O clube é obrigatório.',            
        ]);
        
        $data['creator_id'] = $request->user()->id;

        $game = Game::create($data);

        return response()->json($game, 201);
    }

    public function update(Request $request, $id)
    {
        $game = Game::find($id);        

        if (!$game) {
            return response()->json([
                'message' => 'Partida não encontrada'
            ], 404);
        }

        $data = $request->validate([
            "title" => 'nullable|string|max:255',
            "description"=> 'nullable|string|max:500',            
            "type"  => 'required|in:public,private',
            "data_time" => 'required',
            "club_id" => 'required',
            "court_id" => 'required',
            "custom_location" => 'nullable|string|max:500',
            "min_level" => 'nullable',
            "max_level" => 'nullable',
            "max_players" => 'nullable',
            "status"     => 'required|in:open,full,in_progress,completed, canceled',
            "price" => 'nullable',
            "cost_per_player" => 'nullable',
            "game_type" => 'required|in:casual, competitive, training',
            "duration_minutes" => 'nullable'            
        ], [
            'type.required' => 'O tipo de partida é obrigatório. Defina entre publica ou privada',
            'status.required' => 'A status da partida é obrigatório.',
            'data_time.required' => 'A data e hora da partida é obrigatório.',
            'club_id.required' => 'O clube é obrigatório.',
            'court_id.required' => 'A quadra é obrigatório.',
            'court_id.required' => 'O clube é obrigatório.',            
        ]);
        
        $data['creator_id'] = $request->user()->id;
        
        $game->update($data);

        return response()->json($game);
    }
}
