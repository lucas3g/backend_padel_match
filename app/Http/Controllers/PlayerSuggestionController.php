<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Sugestão de Jogadores",
 *     description="Sugestão de jogadores para convite em partidas"
 * )
 */
class PlayerSuggestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/game/{game}/suggest-players",
     *     tags={"Sugestão de Jogadores"},
     *     summary="Sugere jogadores para uma partida existente",
     *     description="Retorna até 20 jogadores sugeridos para convidar, ordenados por pontuação de relevância (favoritos, partidas juntos, clube preferido, amigos, mesma cidade). Exclui jogadores já na partida e jogadores bloqueados.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="game",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de jogadores sugeridos com pontuação",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="full_name", type="string", example="Carlos Lima"),
     *                 @OA\Property(property="level", type="integer", example=4),
     *                 @OA\Property(property="side", type="string", example="right", nullable=true),
     *                 @OA\Property(property="profile_image_url", type="string", example="https://...", nullable=true),
     *                 @OA\Property(property="score", type="integer", example=65, description="Pontuação de relevância (máx 90)"),
     *                 @OA\Property(property="is_favorite", type="boolean", example=true),
     *                 @OA\Property(property="games_together", type="integer", example=3, description="Número de partidas jogadas juntos"),
     *                 @OA\Property(property="club_match", type="boolean", example=true, description="Prefere o mesmo clube"),
     *                 @OA\Property(property="is_friend", type="boolean", example=false),
     *                 @OA\Property(property="same_city", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=404, description="Partida não encontrada")
     * )
     */
    public function forGame(Request $request, Game $game): JsonResponse
    {
        $currentPlayer = $request->user()->player;
        if (!$currentPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 400);
        }

        $alreadyInGame = $game->players()->pluck('players.id')->toArray();

        return response()->json(
            $this->buildSuggestions(
                $currentPlayer,
                $game->club_id,
                $game->min_level,
                $game->max_level,
                $alreadyInGame
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/players/suggest",
     *     tags={"Sugestão de Jogadores"},
     *     summary="Sugere jogadores antes da criação da partida",
     *     description="Retorna até 20 jogadores sugeridos com base em filtros opcionais de clube e nível. Útil para montar o grupo antes de criar a partida.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="club_id",
     *         in="query",
     *         required=false,
     *         description="ID do clube para filtrar preferências de local",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="min_level",
     *         in="query",
     *         required=false,
     *         description="Nível mínimo do jogador (1 a 10)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="max_level",
     *         in="query",
     *         required=false,
     *         description="Nível máximo do jogador (1 a 10)",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de jogadores sugeridos com pontuação",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="full_name", type="string", example="Ana Souza"),
     *                 @OA\Property(property="level", type="integer", example=3),
     *                 @OA\Property(property="side", type="string", example="left", nullable=true),
     *                 @OA\Property(property="profile_image_url", type="string", nullable=true),
     *                 @OA\Property(property="score", type="integer", example=30),
     *                 @OA\Property(property="is_favorite", type="boolean", example=false),
     *                 @OA\Property(property="games_together", type="integer", example=0),
     *                 @OA\Property(property="club_match", type="boolean", example=false),
     *                 @OA\Property(property="is_friend", type="boolean", example=false),
     *                 @OA\Property(property="same_city", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=422, description="Parâmetros inválidos")
     * )
     */
    public function standalone(Request $request): JsonResponse
    {
        $currentPlayer = $request->user()->player;
        if (!$currentPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 400);
        }

        $request->validate([
            'club_id'   => 'nullable|integer|exists:clubs,id',
            'min_level' => 'nullable|integer|min:1|max:10',
            'max_level' => 'nullable|integer|min:1|max:10|gte:min_level',
        ]);

        return response()->json(
            $this->buildSuggestions(
                $currentPlayer,
                $request->filled('club_id') ? (int) $request->input('club_id') : null,
                $request->filled('min_level') ? (int) $request->input('min_level') : null,
                $request->filled('max_level') ? (int) $request->input('max_level') : null,
                []
            )
        );
    }

    private function buildSuggestions(
        Player $currentPlayer,
        ?int $clubId,
        ?int $minLevel,
        ?int $maxLevel,
        array $excludePlayerIds
    ): array {
        // 1. IDs bloqueados (bidirecional)
        $blockedIds = DB::table('friends')
            ->where('status', 'blocked')
            ->where(function ($q) use ($currentPlayer) {
                $q->where('player_id', $currentPlayer->id)
                  ->orWhere('friend_id', $currentPlayer->id);
            })
            ->get()
            ->map(fn ($row) => $row->player_id == $currentPlayer->id
                ? $row->friend_id
                : $row->player_id
            )
            ->toArray();

        // 2. Contagem de partidas jogadas juntos
        $myGameIds = DB::table('game_players')
            ->where('player_id', $currentPlayer->id)
            ->pluck('game_id')
            ->toArray();

        $gameTogetherCounts = [];
        if (!empty($myGameIds)) {
            DB::table('game_players')
                ->select('player_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('game_id', $myGameIds)
                ->where('player_id', '!=', $currentPlayer->id)
                ->groupBy('player_id')
                ->get()
                ->each(function ($row) use (&$gameTogetherCounts) {
                    $gameTogetherCounts[$row->player_id] = (int) $row->cnt;
                });
        }

        // 3. Favoritos, amigos aceitos e clubes favoritos
        $favoriteIds = DB::table('player_favorites')
            ->where('player_id', $currentPlayer->id)
            ->pluck('favorite_player_id')
            ->toArray();

        $favoriteClubIds = DB::table('player_favorite_clubs')
            ->where('player_id', $currentPlayer->id)
            ->pluck('club_id')
            ->toArray();

        $friendIds = DB::table('friends')
            ->where('status', 'accepted')
            ->where(function ($q) use ($currentPlayer) {
                $q->where('player_id', $currentPlayer->id)
                  ->orWhere('friend_id', $currentPlayer->id);
            })
            ->get()
            ->map(fn ($row) => $row->player_id == $currentPlayer->id
                ? $row->friend_id
                : $row->player_id
            )
            ->toArray();

        // 4. Candidatos com filtros base
        $allExcludeIds = array_unique(array_merge($excludePlayerIds, $blockedIds, [$currentPlayer->id]));

        $query = Player::query()
            ->where('is_active', true)
            ->disponiveis()
            ->whereNotIn('id', $allExcludeIds);

        if ($minLevel !== null) {
            $query->where('level', '>=', $minLevel);
        }
        if ($maxLevel !== null) {
            $query->where('level', '<=', $maxLevel);
        }

        $candidates = $query
            ->select('id', 'full_name', 'level', 'side', 'profile_image_url', 'municipio_ibge')
            ->get();

        // 5. Clubes favoritos dos candidatos (batch para evitar N+1)
        $candidateIds = $candidates->pluck('id')->toArray();
        $candidateFavoriteClubs = DB::table('player_favorite_clubs')
            ->whereIn('player_id', $candidateIds)
            ->get()
            ->groupBy('player_id')
            ->map(fn ($rows) => $rows->pluck('club_id')->toArray());

        // 6. Pontuar e ordenar
        $currentMunicipio = $currentPlayer->municipio_ibge;

        return $candidates
            ->map(function (Player $p) use (
                $favoriteIds, $gameTogetherCounts, $clubId,
                $friendIds, $currentMunicipio, $favoriteClubIds,
                $candidateFavoriteClubs
            ) {
                $isFavorite    = in_array($p->id, $favoriteIds);
                $gamesCount    = $gameTogetherCounts[$p->id] ?? 0;
                $isFriend      = in_array($p->id, $friendIds);
                $playerClubIds = $candidateFavoriteClubs[$p->id] ?? [];
                $sameCity      = $currentMunicipio !== null && $p->municipio_ibge === $currentMunicipio;

                $clubMatch = false;
                if ($clubId !== null && in_array($clubId, $playerClubIds)) {
                    $clubMatch = true;
                } elseif (!empty($favoriteClubIds) && !empty(array_intersect($favoriteClubIds, $playerClubIds))) {
                    $clubMatch = true;
                }

                $score = ($isFavorite ? 30 : 0)
                       + (min($gamesCount, 5) * 5)
                       + ($clubMatch ? 20 : 0)
                       + ($isFriend ? 10 : 0)
                       + ($sameCity ? 5 : 0);

                return [
                    'id'                => $p->id,
                    'full_name'         => $p->full_name,
                    'level'             => $p->level,
                    'side'              => $p->side,
                    'profile_image_url' => $p->profile_image_url,
                    'score'             => $score,
                    'is_favorite'       => $isFavorite,
                    'games_together'    => $gamesCount,
                    'club_match'        => $clubMatch,
                    'is_friend'         => $isFriend,
                    'same_city'         => $sameCity,
                ];
            })
            ->sortByDesc('score')
            ->take(20)
            ->values()
            ->toArray();
    }
}
