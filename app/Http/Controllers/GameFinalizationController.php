<?php

namespace App\Http\Controllers;

use App\Actions\Games\FinalizeRankingGameAction;
use App\Enums\GameType;
use App\Events\GameFinalized;
use App\Jobs\UpdateRankingJob;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\PlayerStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Finalização de Partida",
 *     description="Endpoints para finalizar partidas e registrar placares"
 * )
 */
class GameFinalizationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/game/{game}/finalize",
     *     tags={"Finalização de Partida"},
     *     summary="Finaliza uma partida",
     *     description="Marca a partida como concluída. Para partidas do tipo 'ranking', winner_team e sets são obrigatórios.
     *     Modos de uso:
     *     1) Apenas finalizar (body vazio) — para partidas casuais/treino;
     *     2) Informar vencedor e/ou times;
     *     3) Registrar placar por sets (múltiplos sets possíveis).
     *     Somente o dono da partida pode finalizar. Atualiza estatísticas dos jogadores quando times e vencedor são informados.
     *     Partidas de ranking disparam atualização do ELO assíncrona.",
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
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="winner_team", type="integer", enum={1,2}, nullable=true, example=1,
     *                 description="Time vencedor (1 ou 2). Obrigatório para partidas de ranking. Se omitido com sets, será calculado automaticamente."),
     *             @OA\Property(
     *                 property="sets",
     *                 type="array",
     *                 nullable=true,
     *                 description="Placar por sets. Obrigatório para partidas de ranking.",
     *                 @OA\Items(
     *                     @OA\Property(property="team1_score", type="integer", example=6),
     *                     @OA\Property(property="team2_score", type="integer", example=3)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="teams",
     *                 type="object",
     *                 nullable=true,
     *                 description="Atribuição de jogadores por time (IDs de players). Necessário para atualizar estatísticas.",
     *                 @OA\Property(property="team1", type="array", @OA\Items(type="integer"), example={1,2}),
     *                 @OA\Property(property="team2", type="array", @OA\Items(type="integer"), example={3,4})
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Partida finalizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Partida finalizada com sucesso"),
     *             @OA\Property(property="ranking_update_queued", type="boolean", example=true,
     *                 description="true quando o ELO do ranking está sendo calculado em background"),
     *             @OA\Property(
     *                 property="game",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="game_type", type="string", example="ranking"),
     *                 @OA\Property(property="winner_team", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="team1_score", type="integer", nullable=true, example=2),
     *                 @OA\Property(property="team2_score", type="integer", nullable=true, example=0),
     *                 @OA\Property(
     *                     property="sets",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="set_number", type="integer", example=1),
     *                         @OA\Property(property="team1_score", type="integer", example=6),
     *                         @OA\Property(property="team2_score", type="integer", example=3)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=403, description="Apenas o dono da partida pode finalizá-la"),
     *     @OA\Response(response=422, description="Partida já finalizada, parâmetros inválidos ou dados obrigatórios do ranking ausentes"),
     *     @OA\Response(response=404, description="Partida não encontrada")
     * )
     */
    public function finalize(Request $request, Game $game): JsonResponse
    {
        $currentPlayer = $request->user()->player;
        if (! $currentPlayer) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 400);
        }

        if ($game->owner_player_id !== $currentPlayer->id) {
            return response()->json(['message' => 'Apenas o dono da partida pode finalizá-la'], 403);
        }

        $request->validate([
            'winner_team'         => 'nullable|integer|in:1,2',
            'sets'                => 'nullable|array|min:1|max:5',
            'sets.*.team1_score'  => 'required_with:sets|integer|min:0|max:99',
            'sets.*.team2_score'  => 'required_with:sets|integer|min:0|max:99',
            'teams'               => 'nullable|array',
            'teams.team1'         => 'required_with:teams|array|min:1|max:2',
            'teams.team2'         => 'required_with:teams|array|min:1|max:2',
            'teams.team1.*'       => 'integer|exists:players,id',
            'teams.team2.*'       => 'integer|exists:players,id',
        ]);

        // Partidas de ranking exigem vencedor e sets
        $isRanking = $game->game_type instanceof GameType
            ? $game->game_type->affectsRanking()
            : $game->game_type === 'ranking';

        if ($isRanking) {
            app(FinalizeRankingGameAction::class)->validate($game, $request->all());
        }

        $setsData   = $request->input('sets');
        $teamsData  = $request->input('teams');
        $winnerTeam = $request->input('winner_team');

        DB::transaction(function () use ($game, $setsData, $teamsData, &$winnerTeam) {
            // Adquirir lock para evitar finalização duplicada concorrente
            $freshGame = Game::lockForUpdate()->findOrFail($game->id);

            if (in_array($freshGame->status, ['completed', 'cancelled', 'canceled'])) {
                abort(422, 'Partida já foi encerrada');
            }

            $team1SetsWon = 0;
            $team2SetsWon = 0;

            // Registrar sets e calcular placar agregado
            if (! empty($setsData)) {
                $game->sets()->delete();

                foreach ($setsData as $index => $set) {
                    GameSet::create([
                        'game_id'     => $game->id,
                        'set_number'  => $index + 1,
                        'team1_score' => $set['team1_score'],
                        'team2_score' => $set['team2_score'],
                    ]);

                    if ($set['team1_score'] > $set['team2_score']) {
                        $team1SetsWon++;
                    } elseif ($set['team2_score'] > $set['team1_score']) {
                        $team2SetsWon++;
                    }
                }

                // Auto-determinar vencedor se não foi informado
                if ($winnerTeam === null && $team1SetsWon !== $team2SetsWon) {
                    $winnerTeam = $team1SetsWon > $team2SetsWon ? 1 : 2;
                }
            }

            // Atribuir jogadores aos times e atualizar game_players
            if (! empty($teamsData)) {
                $gamePlayerIds = $game->players()->pluck('players.id')->toArray();

                foreach ([1, 2] as $teamNumber) {
                    $teamKey = 'team' . $teamNumber;
                    foreach ($teamsData[$teamKey] ?? [] as $playerId) {
                        if (in_array($playerId, $gamePlayerIds)) {
                            $game->players()->updateExistingPivot($playerId, ['team' => $teamNumber]);
                        }
                    }
                }

                // Atualizar estatísticas gerais dos jogadores se temos vencedor
                if ($winnerTeam !== null) {
                    $this->updatePlayerStats($teamsData, $winnerTeam);
                }
            }

            // Persistir resultado no game
            $game->update([
                'status'      => 'completed',
                'winner_team' => $winnerTeam,
                'team1_score' => ! empty($setsData) ? $team1SetsWon : $game->team1_score,
                'team2_score' => ! empty($setsData) ? $team2SetsWon : $game->team2_score,
            ]);
        });

        event(new GameFinalized($game));

        // Despachar job de ranking assíncrono para partidas do tipo ranking
        $rankingUpdateQueued = false;
        if ($isRanking) {
            UpdateRankingJob::dispatch($game->id);
            $rankingUpdateQueued = true;
        }

        $game->load('sets');

        return response()->json([
            'message'               => 'Partida finalizada com sucesso',
            'ranking_update_queued' => $rankingUpdateQueued,
            'game'                  => [
                'id'          => $game->id,
                'status'      => $game->status,
                'game_type'   => $game->game_type instanceof GameType
                    ? $game->game_type->value
                    : $game->game_type,
                'winner_team' => $game->winner_team,
                'team1_score' => $game->team1_score,
                'team2_score' => $game->team2_score,
                'sets'        => $game->sets->map(fn ($s) => [
                    'set_number'  => $s->set_number,
                    'team1_score' => $s->team1_score,
                    'team2_score' => $s->team2_score,
                ]),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/game/{game}/sets",
     *     tags={"Finalização de Partida"},
     *     summary="Retorna os sets de uma partida",
     *     description="Lista os placares por set de uma partida, ordenados pelo número do set.",
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
     *         description="Sets da partida",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="set_number", type="integer", example=1),
     *                 @OA\Property(property="team1_score", type="integer", example=6),
     *                 @OA\Property(property="team2_score", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Partida não encontrada")
     * )
     */
    public function sets(Game $game): JsonResponse
    {
        return response()->json(
            $game->sets()->get(['set_number', 'team1_score', 'team2_score'])
        );
    }

    /**
     * Atualiza estatísticas gerais (total_matches, wins, losses, streak, win_rate)
     * para partidas de qualquer tipo. Para ranking, o ELO é tratado pelo UpdateRankingJob.
     */
    private function updatePlayerStats(array $teamsData, int $winnerTeam): void
    {
        foreach ([1, 2] as $teamNumber) {
            $teamKey  = 'team' . $teamNumber;
            $isWinner = $teamNumber === $winnerTeam;

            foreach ($teamsData[$teamKey] ?? [] as $playerId) {
                $stat = PlayerStat::firstOrCreate(
                    ['player_id' => $playerId],
                    [
                        'total_matches'   => 0,
                        'wins'            => 0,
                        'losses'          => 0,
                        'win_rate'        => 0,
                        'current_streak'  => 0,
                        'longest_streak'  => 0,
                        'average_elo'     => 1000,
                        'ranking_matches' => 0,
                        'ranking_wins'    => 0,
                        'ranking_losses'  => 0,
                    ]
                );

                $stat->total_matches++;

                if ($isWinner) {
                    $stat->wins++;
                    // Streak correto: positivo = sequência de vitórias
                    $stat->current_streak = ($stat->current_streak < 0 ? 0 : $stat->current_streak) + 1;

                    if ($stat->current_streak > $stat->longest_streak) {
                        $stat->longest_streak = $stat->current_streak;
                    }
                } else {
                    $stat->losses++;
                    // Streak correto: negativo = sequência de derrotas (correção do bug original)
                    $stat->current_streak = ($stat->current_streak > 0 ? 0 : $stat->current_streak) - 1;
                }

                $stat->win_rate = round(($stat->wins / $stat->total_matches) * 100, 2);

                $stat->save();
            }
        }
    }
}
