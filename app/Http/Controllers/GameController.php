<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Games",
 *     description="Gerenciamento de partidas"
 * )
 */
class GameController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/game",
     *     tags={"Games"},
     *     summary="Lista todos as partidas",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista as partidas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function show(Request $request)
    {
        return response()->json(
            Game::all()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/game",
     *     tags={"Games"},
     *     summary="Cria uma nova partida",
     *     description="Cria uma nova partida associada ao usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","data_time","club_id","court_id","status","game_type"},
     *             @OA\Property(property="title", type="string", example="Partida de Padel"),
     *             @OA\Property(property="description", type="string", example="Partida amistosa"),
     *             @OA\Property(property="type", type="string", enum={"public","private"}, example="public"),
     *             @OA\Property(property="data_time", type="string", format="date-time", example="2026-02-10 19:00:00"),
     *             @OA\Property(property="club_id", type="integer", example=1),
     *             @OA\Property(property="court_id", type="integer", example=3),
     *             @OA\Property(property="custom_location", type="string", example="Quadra externa"),
     *             @OA\Property(property="min_level", type="integer", example=2),
     *             @OA\Property(property="max_level", type="integer", example=4),
     *             @OA\Property(property="max_players", type="integer", example=4),
     *             @OA\Property(property="status", type="string", enum={"open","full","in_progress","completed","canceled"}, example="open"),
     *             @OA\Property(property="price", type="number", format="float", example=120),
     *             @OA\Property(property="cost_per_player", type="number", format="float", example=30),
     *             @OA\Property(property="game_type", type="string", enum={"casual","competitive","training"}, example="casual"),
     *             @OA\Property(property="duration_minutes", type="integer", example=90)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Partida criada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            "title" => 'nullable|string|max:255',
            "description" => 'nullable|string|max:500',
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

    /**
     * @OA\Put(
     *     path="/api/game/{id}",
     *     tags={"Games"},
     *     summary="Atualiza uma partida",
     *     description="Atualiza os dados de uma partida existente",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Clube atualizado",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Partida não encontrada"
     *     )
     * )
     */
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
            "description" => 'nullable|string|max:500',
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
