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
            "game_type"  => 'required|in:casual,competitive,training',
            "status"     => 'required|in:open,full,in_progress,completed, canceled',
            "court_id"   => 'required|integer',
            "custom_location"=> 'nullable',
            "scheduled_date" => 'required',
            "scheduled_time" => 'required',
            "duration_minutes" => 'nullable',
            "min_level" => 'nullable',
            "max_level" => 'nullable',
            "max_players" => 'nullable',
            "current_players" => 'nullable'
        ], [
            'game_type.required' => 'O tipo de partida é obrigatório.',
            'status.required' => 'A status da partida é obrigatório.',
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
            "game_type"  => 'required|in:casual,competitive,training',
            "status"     => 'required|in:open,full,in_progress,completed, canceled',
            "court_id"   => 'required|integer',
            "custom_location"=> 'nullable',
            "scheduled_date" => 'required',
            "scheduled_time" => 'required',
            "duration_minutes" => 'nullable',
            "min_level" => 'nullable',
            "max_level" => 'nullable',
            "max_players" => 'nullable',
            "current_players" => 'nullable'
        ], [
            'game_type.required' => 'O tipo de partida é obrigatório.',
            'status.required' => 'A status da partida é obrigatório.',
            'court_id.required' => 'O clube é obrigatório.',
        ]);
        
        $data['creator_id'] = $request->user()->id;
        
        $game->update($data);

        return response()->json($game);
    }
}
