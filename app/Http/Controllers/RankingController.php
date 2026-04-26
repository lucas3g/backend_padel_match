<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubPlayerRanking;
use App\Models\ClubRanking;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Ranking",
 *     description="Endpoints para consulta do ranking de jogadores e clubes"
 * )
 */
class RankingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ranking/players",
     *     tags={"Ranking"},
     *     summary="Ranking geral de jogadores",
     *     description="Retorna a lista paginada de jogadores ordenada pela posição no ranking (baseado em ELO). Apenas jogadores com ao menos uma partida de ranking são incluídos.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="page", in="query", description="Página", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página (máx 100)", @OA\Schema(type="integer", example=25)),
     *     @OA\Parameter(name="level_min", in="query", description="Filtrar por nível mínimo (1-7)", @OA\Schema(type="integer", example=4)),
     *     @OA\Parameter(name="level_max", in="query", description="Filtrar por nível máximo (1-7)", @OA\Schema(type="integer", example=7)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ranking de jogadores",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="ranking_position", type="integer", example=1),
     *                 @OA\Property(property="player_id", type="integer", example=7),
     *                 @OA\Property(property="full_name", type="string", example="Carlos Lima"),
     *                 @OA\Property(property="level", type="integer", example=8),
     *                 @OA\Property(property="side", type="string", example="right"),
     *                 @OA\Property(property="profile_image_url", type="string", nullable=true),
     *                 @OA\Property(property="ranking_points", type="integer", example=1284),
     *                 @OA\Property(property="ranking_matches", type="integer", example=15),
     *                 @OA\Property(property="ranking_wins", type="integer", example=12),
     *                 @OA\Property(property="ranking_losses", type="integer", example=3),
     *                 @OA\Property(property="win_rate", type="number", example=80.0)
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function players(Request $request): JsonResponse
    {
        $request->validate([
            'per_page'  => 'nullable|integer|min:1|max:100',
            'level_min' => 'nullable|integer|min:1|max:7',
            'level_max' => 'nullable|integer|min:1|max:7',
        ]);

        $perPage = $request->integer('per_page', 25);

        $query = Player::with('stats')
            ->whereNotNull('ranking_position')
            ->where('is_active', true)
            ->orderBy('ranking_position');

        if ($request->filled('level_min')) {
            $query->where('level', '>=', $request->integer('level_min'));
        }

        if ($request->filled('level_max')) {
            $query->where('level', '<=', $request->integer('level_max'));
        }

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (Player $player) => [
            'ranking_position' => $player->ranking_position,
            'player_id'        => $player->id,
            'full_name'        => $player->full_name,
            'level'            => $player->level,
            'side'             => $player->side,
            'profile_image_url' => $player->profile_image_url,
            'ranking_points'   => $player->ranking_points,
            'ranking_matches'  => $player->stats?->ranking_matches ?? 0,
            'ranking_wins'     => $player->stats?->ranking_wins ?? 0,
            'ranking_losses'   => $player->stats?->ranking_losses ?? 0,
            'win_rate'         => $player->stats?->win_rate ?? 0,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/ranking/players/{player}",
     *     tags={"Ranking"},
     *     summary="Card de ranking de um jogador",
     *     description="Retorna os dados de ranking de um jogador específico, incluindo histórico dos últimos 5 jogos de ranking com delta ELO.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="player", in="path", required=true, description="ID do jogador", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Card de ranking do jogador",
     *         @OA\JsonContent(
     *             @OA\Property(property="player_id", type="integer"),
     *             @OA\Property(property="full_name", type="string"),
     *             @OA\Property(property="level", type="integer"),
     *             @OA\Property(property="ranking_position", type="integer", nullable=true),
     *             @OA\Property(property="ranking_points", type="integer"),
     *             @OA\Property(property="ranking_matches", type="integer"),
     *             @OA\Property(property="ranking_wins", type="integer"),
     *             @OA\Property(property="ranking_losses", type="integer"),
     *             @OA\Property(property="win_rate", type="number"),
     *             @OA\Property(property="current_streak", type="integer"),
     *             @OA\Property(property="recent_elo_history", type="array", @OA\Items(
     *                 @OA\Property(property="game_id", type="integer"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="elo_before", type="number"),
     *                 @OA\Property(property="elo_after", type="number"),
     *                 @OA\Property(property="elo_delta", type="integer"),
     *                 @OA\Property(property="won", type="boolean")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Jogador não encontrado")
     * )
     */
    public function playerCard(Player $player): JsonResponse
    {
        $player->load('stats');

        // Histórico ELO: últimos 5 jogos de ranking onde o jogador participou
        $eloHistory = DB::table('game_players as gp')
            ->join('games as g', 'g.id', '=', 'gp.game_id')
            ->where('gp.player_id', $player->id)
            ->where('g.game_type', 'ranking')
            ->where('g.status', 'completed')
            ->whereNotNull('gp.elo_after')
            ->orderByDesc('g.data_time')
            ->limit(5)
            ->select([
                'g.id as game_id',
                'g.data_time as date',
                'g.winner_team',
                'gp.team',
                'gp.elo_before',
                'gp.elo_after',
                'gp.elo_delta',
            ])
            ->get()
            ->map(fn ($row) => [
                'game_id'    => $row->game_id,
                'date'       => $row->date,
                'elo_before' => $row->elo_before,
                'elo_after'  => $row->elo_after,
                'elo_delta'  => $row->elo_delta,
                'won'        => $row->team === $row->winner_team,
            ]);

        // Clubes onde o player está rankeado (com ao menos 3 partidas)
        $clubsRanking = ClubPlayerRanking::with('club:id,name,city,state')
            ->where('player_id', $player->id)
            ->whereNotNull('club_position')
            ->orderBy('club_position')
            ->get()
            ->map(fn (ClubPlayerRanking $cpr) => [
                'club_id'                 => $cpr->club_id,
                'club_name'               => $cpr->club->name,
                'club_position'           => $cpr->club_position,
                'club_elo'                => $cpr->club_elo,
                'ranking_matches_at_club' => $cpr->ranking_matches_at_club,
                'win_rate_at_club'        => $cpr->win_rate_at_club,
            ]);

        return response()->json([
            'player_id'          => $player->id,
            'full_name'          => $player->full_name,
            'level'              => $player->level,
            'side'               => $player->side,
            'profile_image_url'  => $player->profile_image_url,
            'ranking_position'   => $player->ranking_position,
            'ranking_points'     => $player->ranking_points,
            'ranking_matches'    => $player->stats?->ranking_matches ?? 0,
            'ranking_wins'       => $player->stats?->ranking_wins ?? 0,
            'ranking_losses'     => $player->stats?->ranking_losses ?? 0,
            'win_rate'           => $player->stats?->win_rate ?? 0,
            'current_streak'     => $player->stats?->current_streak ?? 0,
            'recent_elo_history' => $eloHistory,
            'clubs_ranking'      => $clubsRanking,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/ranking/clubs",
     *     tags={"Ranking"},
     *     summary="Ranking geral de clubes",
     *     description="Retorna a lista paginada de clubes ordenada pela posição no ranking (baseado na média de ELO dos jogadores).",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="page", in="query", description="Página", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página (máx 50)", @OA\Schema(type="integer", example=20)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ranking de clubes",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="ranking_position", type="integer", example=1),
     *                 @OA\Property(property="club_id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Club Padel Norte"),
     *                 @OA\Property(property="city", type="string", example="Chapecó", nullable=true),
     *                 @OA\Property(property="state", type="string", nullable=true),
     *                 @OA\Property(property="average_elo", type="number", example=1187.4),
     *                 @OA\Property(property="active_players", type="integer", example=23),
     *                 @OA\Property(property="total_ranking_games", type="integer", example=41),
     *                 @OA\Property(property="win_rate", type="number", example=62.5)
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function clubs(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $perPage = $request->integer('per_page', 20);

        $paginated = ClubRanking::with(['club:id,name,city,state', 'club.municipio:codigo_ibge,descricao'])
            ->whereNotNull('ranking_position')
            ->orderBy('ranking_position')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (ClubRanking $cr) => [
            'ranking_position'    => $cr->ranking_position,
            'club_id'             => $cr->club_id,
            'name'                => $cr->club?->name,
            'city'                => $cr->club?->municipio?->descricao,
            'state'               => $cr->club?->state,
            'average_elo'         => $cr->average_elo,
            'active_players'      => $cr->active_players,
            'total_ranking_games' => $cr->total_ranking_games,
            'win_rate'            => $cr->win_rate,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/ranking/clubs/{club}",
     *     tags={"Ranking"},
     *     summary="Card de ranking de um clube",
     *     description="Retorna os dados de ranking de um clube específico, incluindo o top 5 de jogadores do clube.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="club", in="path", required=true, description="ID do clube", @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Card de ranking do clube",
     *         @OA\JsonContent(
     *             @OA\Property(property="club_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="city", type="string", example="Chapecó", nullable=true),
     *             @OA\Property(property="state", type="string", example="SC", nullable=true),
     *             @OA\Property(property="ranking_position", type="integer", nullable=true),
     *             @OA\Property(property="average_elo", type="number"),
     *             @OA\Property(property="active_players", type="integer"),
     *             @OA\Property(property="total_ranking_games", type="integer"),
     *             @OA\Property(property="win_rate", type="number"),
     *             @OA\Property(property="last_computed_at", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="top_players", type="array", @OA\Items(
     *                 @OA\Property(property="player_id", type="integer"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="ranking_position", type="integer"),
     *                 @OA\Property(property="ranking_points", type="integer"),
     *                 @OA\Property(property="level", type="integer")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Clube não encontrado")
     * )
     */
    public function clubCard(Club $club): JsonResponse
    {
        $club->load('municipio:codigo_ibge,descricao');
        $rankingStats = $club->rankingStats;

        // Top 5 jogadores do clube por club_elo (ELO específico do clube)
        $topPlayers = ClubPlayerRanking::with('player:id,full_name,level,side,profile_image_url,ranking_points,ranking_position,is_active')
            ->where('club_id', $club->id)
            ->whereNotNull('club_position')
            ->whereHas('player', fn ($q) => $q->where('is_active', true))
            ->orderBy('club_position')
            ->limit(5)
            ->get()
            ->map(fn (ClubPlayerRanking $cpr) => [
                'player_id'               => $cpr->player_id,
                'full_name'               => $cpr->player->full_name,
                'level'                   => $cpr->player->level,
                'club_position'           => $cpr->club_position,
                'club_elo'                => $cpr->club_elo,
                'global_position'         => $cpr->player->ranking_position,
                'global_elo'              => $cpr->player->ranking_points,
                'ranking_matches_at_club' => $cpr->ranking_matches_at_club,
                'win_rate_at_club'        => $cpr->win_rate_at_club,
            ]);

        return response()->json([
            'club_id'             => $club->id,
            'name'                => $club->name,
            'city'                => $club->municipio?->descricao,
            'state'               => $club->state,
            'ranking_position'    => $rankingStats?->ranking_position,
            'average_elo'         => $rankingStats?->average_elo,
            'active_players'      => $rankingStats?->active_players ?? 0,
            'total_ranking_games' => $rankingStats?->total_ranking_games ?? 0,
            'win_rate'            => $rankingStats?->win_rate ?? 0,
            'last_computed_at'    => $rankingStats?->last_computed_at?->toISOString(),
            'top_players'         => $topPlayers,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/ranking/clubs/{club}/players",
     *     tags={"Ranking"},
     *     summary="Ranking de jogadores de um clube",
     *     description="Retorna a lista paginada de jogadores rankeados em um clube específico, ordenados pelo ELO do clube. Apenas jogadores com ao menos 3 partidas de ranking no clube são incluídos.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(name="club", in="path", required=true, description="ID do clube", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", description="Página", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", description="Itens por página (máx 100)", @OA\Schema(type="integer", example=25)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ranking de jogadores do clube",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="club_position", type="integer", example=1),
     *                 @OA\Property(property="player_id", type="integer", example=7),
     *                 @OA\Property(property="full_name", type="string", example="Carlos Lima"),
     *                 @OA\Property(property="level", type="integer", example=8),
     *                 @OA\Property(property="side", type="string", example="right"),
     *                 @OA\Property(property="profile_image_url", type="string", nullable=true),
     *                 @OA\Property(property="club_elo", type="integer", example=1245),
     *                 @OA\Property(property="global_position", type="integer", nullable=true, example=3),
     *                 @OA\Property(property="global_elo", type="integer", example=1284),
     *                 @OA\Property(property="ranking_matches_at_club", type="integer", example=10),
     *                 @OA\Property(property="ranking_wins_at_club", type="integer", example=7),
     *                 @OA\Property(property="ranking_losses_at_club", type="integer", example=3),
     *                 @OA\Property(property="win_rate_at_club", type="number", example=70.0)
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Clube não encontrado")
     * )
     */
    public function clubPlayers(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $request->integer('per_page', 25);

        $paginated = ClubPlayerRanking::with('player:id,full_name,level,side,profile_image_url,ranking_points,ranking_position,is_active')
            ->where('club_id', $club->id)
            ->whereNotNull('club_position')
            ->whereHas('player', fn ($q) => $q->where('is_active', true))
            ->orderBy('club_position')
            ->paginate($perPage);

        $data = $paginated->getCollection()->map(fn (ClubPlayerRanking $cpr) => [
            'club_position'           => $cpr->club_position,
            'player_id'               => $cpr->player_id,
            'full_name'               => $cpr->player->full_name,
            'level'                   => $cpr->player->level,
            'side'                    => $cpr->player->side,
            'profile_image_url'       => $cpr->player->profile_image_url,
            'club_elo'                => $cpr->club_elo,
            'global_position'         => $cpr->player->ranking_position,
            'global_elo'              => $cpr->player->ranking_points,
            'ranking_matches_at_club' => $cpr->ranking_matches_at_club,
            'ranking_wins_at_club'    => $cpr->ranking_wins_at_club,
            'ranking_losses_at_club'  => $cpr->ranking_losses_at_club,
            'win_rate_at_club'        => $cpr->win_rate_at_club,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }
}
