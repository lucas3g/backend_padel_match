<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Jogadores Favoritos",
 *     description="Gerenciamento dos jogadores favoritos do jogador (somente amigos aceitos)"
 * )
 */
class PlayerFavoritePlayerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/me/player/jogadores-favoritos",
     *     tags={"Jogadores Favoritos"},
     *     summary="Lista os jogadores favoritos do player",
     *     description="Retorna os jogadores marcados como favoritos pelo jogador autenticado, com filtros opcionais",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="full_name", in="query", required=false, description="Filtrar por nome (busca parcial)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="side", in="query", required=false, description="Filtrar por lado (left, right, both)", @OA\Schema(type="string", enum={"left","right","both"})),
     *     @OA\Parameter(name="uf", in="query", required=false, description="Filtrar por UF", @OA\Schema(type="string", example="SP")),
     *     @OA\Parameter(name="municipio_ibge", in="query", required=false, description="Filtrar por código IBGE do município", @OA\Schema(type="string", example="3550308")),
     *     @OA\Parameter(name="level", in="query", required=false, description="Filtrar por nível", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="apenas_disponiveis", in="query", required=false, description="Apenas jogadores disponíveis", @OA\Schema(type="boolean")),
     *
     *     @OA\Response(response=200, description="Lista de jogadores favoritos", @OA\JsonContent(type="array", @OA\Items(type="object"))),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        $favorites = $player->favorites()
            ->when($request->query('full_name'), fn ($q, $name) => $q->where('players.full_name', 'like', "%{$name}%"))
            ->when($request->query('level'), fn ($q, $level) => $q->where('players.level', $level))
            ->when($request->query('side'), function ($q, $side) {
                if ($side === 'both') {
                    return $q;
                }
                return $q->whereIn('players.side', [$side, 'both']);
            })
            ->when($request->query('uf'), fn ($q, $uf) => $q->where('players.uf', strtoupper($uf)))
            ->when($request->query('municipio_ibge'), fn ($q, $codigo) => $q->where('players.municipio_ibge', $codigo))
            ->when($request->boolean('apenas_disponiveis'), function ($q) {
                return $q->where(function ($inner) {
                    $inner->where('players.disponibilidade', 'disponivel')
                        ->orWhereNotNull('players.disponivel_ate')
                        ->where('players.disponivel_ate', '<=', now());
                });
            })
            ->with('municipio')
            ->get();

        return response()->json($favorites);
    }

    /**
     * @OA\Post(
     *     path="/api/me/player/jogadores-favoritos/{player}",
     *     tags={"Jogadores Favoritos"},
     *     summary="Adiciona um jogador aos favoritos",
     *     description="Marca um jogador como favorito. O jogador deve ser um amigo aceito.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="player", in="path", required=true, description="ID do jogador", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=201, description="Jogador adicionado aos favoritos"),
     *     @OA\Response(response=400, description="Jogadores não são amigos ou tentativa de favoritar a si mesmo"),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=409, description="Jogador já está nos favoritos")
     * )
     */
    public function store(Request $request, Player $player): JsonResponse
    {
        $myPlayer = $request->user()->player;

        if (!$myPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        if ($myPlayer->id === $player->id) {
            return response()->json(['message' => 'Não é possível favoritar a si mesmo'], 400);
        }

        $friendship = Friend::where(function ($q) use ($myPlayer, $player) {
            $q->where('player_id', $myPlayer->id)->where('friend_id', $player->id);
        })->orWhere(function ($q) use ($myPlayer, $player) {
            $q->where('player_id', $player->id)->where('friend_id', $myPlayer->id);
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json(['message' => 'Somente amigos aceitos podem ser favoritados'], 400);
        }

        if ($myPlayer->favorites()->where('favorite_player_id', $player->id)->exists()) {
            return response()->json(['message' => 'Jogador já está nos favoritos'], 409);
        }

        $myPlayer->favorites()->attach($player->id);

        return response()->json([
            'message' => 'Jogador adicionado aos favoritos',
            'jogador' => $myPlayer->favorites()->where('favorite_player_id', $player->id)->with('municipio')->first(),
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/me/player/jogadores-favoritos/{player}",
     *     tags={"Jogadores Favoritos"},
     *     summary="Remove um jogador dos favoritos",
     *     description="Desmarca um jogador como favorito do jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="player", in="path", required=true, description="ID do jogador", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Jogador removido dos favoritos"),
     *     @OA\Response(response=404, description="Jogador não está nos favoritos ou player não encontrado")
     * )
     */
    public function destroy(Request $request, Player $player): JsonResponse
    {
        $myPlayer = $request->user()->player;

        if (!$myPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        if (!$myPlayer->favorites()->where('favorite_player_id', $player->id)->exists()) {
            return response()->json(['message' => 'Jogador não está nos favoritos'], 404);
        }

        $myPlayer->favorites()->detach($player->id);

        return response()->json(['message' => 'Jogador removido dos favoritos']);
    }

    /**
     * @OA\Put(
     *     path="/api/me/player/jogadores-favoritos",
     *     tags={"Jogadores Favoritos"},
     *     summary="Sincroniza a lista de jogadores favoritos",
     *     description="Substitui toda a lista de favoritos pelos IDs informados. Envie array vazio para limpar todos.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"player_ids"},
     *             @OA\Property(property="player_ids", type="array", @OA\Items(type="integer"), example={1, 3, 7})
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Lista de favoritos sincronizada"),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=422, description="IDs inválidos")
     * )
     */
    public function sync(Request $request): JsonResponse
    {
        $myPlayer = $request->user()->player;

        if (!$myPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        $data = $request->validate([
            'player_ids'   => 'required|array',
            'player_ids.*' => 'integer|exists:players,id',
        ], [
            'player_ids.required'  => 'A lista de jogadores é obrigatória.',
            'player_ids.array'     => 'player_ids deve ser um array.',
            'player_ids.*.exists'  => 'Um ou mais jogadores informados não existem.',
        ]);

        $myPlayer->favorites()->sync($data['player_ids']);

        return response()->json([
            'message'   => 'Jogadores favoritos atualizados com sucesso',
            'jogadores' => $myPlayer->favorites()->with('municipio')->get(),
        ]);
    }
}
