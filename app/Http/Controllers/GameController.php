<?php

namespace App\Http\Controllers;

use App\Events\PlayerJoinedGame;
use App\Events\PlayerLeftGame;
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
     *     summary="Lista partidas do usário",
     *     description="Lista todas as partidas do usuário logado e quem está na partida",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dados da partida e players daquela partida",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        /*
        $games = Game::all();
        return response()->json($games);
        */
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $games = $player->games()
            ->where('status', 'open')
            ->with([
                'players:id,full_name,level,side', //define colunas para retornar
                'owner:id,full_name',
                'club:id,name,city,state',
                'court:id,club_id,name,type,covered'
            ])
            ->get()
            ->each(function ($game) {
                $game->makeHidden(['created_at', 'updated_at']);
                $game->players->each(function ($player) {
                    $player->pivot->makeHidden(['created_at', 'updated_at']);
                });
            });

        return response()->json($games);
    }

    /**
     * @OA\Get(
     *     path="/api/game/available",
     *     tags={"Games"},
     *     summary="Lista partidas públicas disponíveis",
     *     description="Retorna todas as partidas públicas com status open onde o usuário logado não é o criador",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de partidas públicas disponíveis",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function available(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $games = Game::where('type', 'public')
            ->where('status', 'open')
            ->where('owner_player_id', '!=', $player->id)
            ->with([
                'players:id,full_name,level,side',
                'owner:id,full_name',
                'club:id,name,city,state',
                'court:id,club_id,name,type,covered'
            ])
            ->get()
            ->each(function ($game) {
                $game->makeHidden(['created_at', 'updated_at']);
                $game->players->each(function ($player) {
                    $player->pivot->makeHidden(['created_at', 'updated_at']);
                });
            });

        return response()->json($games);
    }

    /**
     * @OA\Get(
     *     path="/api/game/id",
     *     tags={"Games"},
     *     summary="Lista uma partida",
     *     description="Lista uma partida conforme id solicitado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista a partida",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $game = Game::with([
            'players:id,full_name,level,side', //define colunas para retornar
            'owner:id,full_name',
            'club:id,name,city,state',
            'court:id,club_id,name,type,covered'
        ])
            ->where('status', 'open') //apenas partidas abertas
            ->findOrFail($id);

        $game->makeHidden(['created_at', 'updated_at']);
        $game->players->each(function ($player) {
            $player->pivot->makeHidden(['created_at', 'updated_at']);
        });

        return response()->json($game);
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
            "data_time" => 'nullable|date',
            "club_id" => 'required|exists:clubs,id',
            "court_id" => 'required|exists:courts,id',
            "custom_location" => 'nullable|string|max:500',
            "min_level" => 'nullable|integer',
            "max_level" => 'nullable|integer',
            "max_players" => 'nullable|integer|min:2',
            "price" => 'nullable|numeric',
            "cost_per_player" => 'nullable|numeric',
            "game_type" => 'required|in:casual,competitive,training',
            "duration_minutes" => 'nullable|integer|min:30'
        ]);

        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $game = new Game($data);
        $game->owner_player_id = $player->id;
        $game->status = 'open';
        $game->save();

        $game->players()->attach($player->id, [
            'joined_at' => now()
        ]);

        return response()->json(
            $game->load('players'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/game/{game}/join",
     *     tags={"Games"},
     *     summary="Entrar em uma partida",
     *     description="Adiciona o player do usuário autenticado a uma partida existente",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="game",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Entrou na partida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Entrou na partida")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Partida privada - acesso não permitido"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partida não encontrada"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflito - partida fechada ou cheia"
     *     )
     * )
     */
    public function join(Request $request, Game $game)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        if ($game->type !== 'public') {
            return response()->json([
                'message' => 'Não é possível entrar em partidas privadas'
            ], 403);
        }

        if ($game->status !== 'open') {
            return response()->json([
                'message' => 'Esta partida não está aberta para novos jogadores'
            ], 409);
        }

        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            return response()->json([
                'message' => 'A partida já atingiu o número máximo de jogadores'
            ], 409);
        }

        $game->players()->syncWithoutDetaching([
            $player->id => ['joined_at' => now()]
        ]);

        // Broadcast para todos os participantes da partida
        event(new PlayerJoinedGame($game, $player));

        // Atualiza status para 'full' quando atingir max_players
        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            $game->update(['status' => 'full']);
        }

        return response()->json([
            'message' => 'Entrou na partida'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/game/{game}/leave",
     *     tags={"Games"},
     *     summary="Sair de uma partida",
     *     description="Remove o player do usuário autenticado de uma partida existente. O criador da partida não pode sair.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="game",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Saiu da partida com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Saiu da partida")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partida não encontrada"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflito - criador não pode sair da partida"
     *     )
     * )
     */
    public function leave(Request $request, Game $game)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        // O criador da partida não pode sair (deve cancelar a partida)
        if ($game->owner_player_id === $player->id) {
            return response()->json([
                'message' => 'O criador da partida não pode sair. Cancele a partida.'
            ], 409);
        }

        $game->players()->detach($player->id);

        // Broadcast para todos os participantes restantes
        event(new PlayerLeftGame($game, $player));

        // Se a partida estava cheia e alguém saiu, reabrir
        if ($game->status === 'full') {
            $game->update(['status' => 'open']);
        }

        return response()->json([
            'message' => 'Saiu da partida'
        ]);
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
            "data_time" => 'nullable|date',
            "club_id" => 'required',
            "court_id" => 'required',
            "custom_location" => 'nullable|string|max:500',
            "min_level" => 'nullable',
            "max_level" => 'nullable',
            "max_players" => 'nullable',
            "status"     => 'required|in:open,full,in_progress,completed,canceled',
            "price" => 'nullable',
            "cost_per_player" => 'nullable',
            "game_type" => 'required|in:casual,competitive,training',
            "duration_minutes" => 'nullable'
        ], [
            'type.required' => 'O tipo de partida é obrigatório. Defina entre publica ou privada',
            'status.required' => 'A status da partida é obrigatório.',
            'data_time.required' => 'A data e hora da partida é obrigatório.',
            'club_id.required' => 'O clube é obrigatório.',
            'court_id.required' => 'A quadra é obrigatório.',
            'court_id.required' => 'O clube é obrigatório.',
        ]);

        $game->update($data);

        return response()->json($game);
    }
}
