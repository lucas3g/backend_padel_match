<?php

namespace App\Services;

use App\Enums\GameType;
use App\Models\Club;
use App\Models\ClubPlayerRanking;
use App\Models\ClubRanking;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerStat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankingService
{
    /**
     * Processa o ranking de uma partida do tipo 'ranking'.
     * Deve ser chamado apenas a partir do UpdateRankingJob.
     * Toda a lógica é executada dentro de uma transação com advisory lock.
     */
    public function processRankingGame(Game $game): void
    {
        if (! $game->game_type instanceof GameType || ! $game->game_type->affectsRanking()) {
            Log::warning('RankingService: partida não é do tipo ranking', ['game_id' => $game->id]);
            return;
        }

        DB::transaction(function () use ($game) {
            // Advisory lock para evitar processamento duplicado concorrente
            $lockKey = crc32('ranking_game_' . $game->id);
            DB::statement("SELECT pg_advisory_xact_lock({$lockKey})");

            // Recarregar game com lock para ter dados frescos
            $game = Game::with([
                'players' => fn ($q) => $q->withPivot('team', 'elo_before', 'elo_after', 'elo_delta'),
                'sets',
            ])->lockForUpdate()->findOrFail($game->id);

            if ($game->winner_team === null) {
                Log::error('RankingService: partida ranking sem vencedor definido', ['game_id' => $game->id]);
                return;
            }

            if ($game->sets->isEmpty()) {
                Log::error('RankingService: partida ranking sem sets registrados', ['game_id' => $game->id]);
                return;
            }

            $team1Players = $game->players->filter(fn ($p) => $p->pivot->team === 1)->values();
            $team2Players = $game->players->filter(fn ($p) => $p->pivot->team === 2)->values();

            if ($team1Players->isEmpty() || $team2Players->isEmpty()) {
                Log::error('RankingService: partida ranking sem jogadores nos dois times', ['game_id' => $game->id]);
                return;
            }

            $team1AvgElo = $team1Players->avg('ranking_points');
            $team2AvgElo = $team2Players->avg('ranking_points');

            $team1IsWinner = $game->winner_team === 1;

            $team1Deltas = $this->calculateEloDeltas($team1Players, $team2AvgElo, $team1IsWinner);
            $team2Deltas = $this->calculateEloDeltas($team2Players, $team1AvgElo, ! $team1IsWinner);

            $this->applyDeltas($team1Deltas, $game, true, $team1IsWinner);
            $this->applyDeltas($team2Deltas, $game, false, ! $team1IsWinner);

            // Calcular ELO por clube (usa apenas as partidas jogadas neste clube)
            $clubId          = $game->club_id;
            $team1ClubAvgElo = $team1Players->avg(fn ($p) => $this->getClubElo($p, $clubId));
            $team2ClubAvgElo = $team2Players->avg(fn ($p) => $this->getClubElo($p, $clubId));

            $team1ClubDeltas = $this->calculateClubEloDeltas($team1Players, $team2ClubAvgElo, $team1IsWinner, $clubId);
            $team2ClubDeltas = $this->calculateClubEloDeltas($team2Players, $team1ClubAvgElo, ! $team1IsWinner, $clubId);

            $this->applyClubDeltas($team1ClubDeltas, $clubId, $team1IsWinner);
            $this->applyClubDeltas($team2ClubDeltas, $clubId, ! $team1IsWinner);

            $this->recalculateAllPositions();
            $this->recalculateClubRanking($game->club_id);
            $this->recalculateClubPlayerPositions($game->club_id);
        });
    }

    /**
     * Calcula os deltas ELO para um time.
     * Retorna array de ['player_id', 'elo_before', 'elo_after', 'delta'].
     */
    public function calculateEloDeltas(Collection $teamPlayers, float $opponentAvgElo, bool $isWinner): array
    {
        $result = [];

        foreach ($teamPlayers as $player) {
            $eloBefore = (float) $player->ranking_points;
            $k         = $this->getKFactor($player);

            // Probabilidade esperada de vitória
            $expected = 1 / (1 + pow(10, ($opponentAvgElo - $eloBefore) / 400));

            // Score real: 1 para vitória, 0 para derrota
            $score = $isWinner ? 1.0 : 0.0;

            $rawDelta = (int) round($k * ($score - $expected));
            $eloAfter = max(100, $eloBefore + $rawDelta);

            // Recalcular delta real após aplicar floor de 100
            $actualDelta = (int) ($eloAfter - $eloBefore);

            $result[] = [
                'player_id'  => $player->id,
                'elo_before' => $eloBefore,
                'elo_after'  => $eloAfter,
                'delta'      => $actualDelta,
            ];
        }

        return $result;
    }

    /**
     * Retorna o K-factor para um jogador baseado no seu nível e número de partidas ranking.
     */
    public function getKFactor(Player $player): int
    {
        $baseK = match (true) {
            $player->level >= 9 => 20,
            $player->level >= 7 => 24,
            $player->level >= 4 => 30,
            default             => 40,
        };

        // Multiplicador para jogadores com poucas partidas de ranking (cold-start)
        $rankingMatches = $player->stats?->ranking_matches ?? 0;
        if ($rankingMatches < 10) {
            return (int) round($baseK * 1.5);
        }

        return $baseK;
    }

    /**
     * Aplica os deltas ELO: atualiza players, pivot game_players e player_stats.
     */
    private function applyDeltas(array $deltas, Game $game, bool $isTeam1, bool $isWinner): void
    {
        foreach ($deltas as $delta) {
            // Atualizar ranking_points no player
            Player::where('id', $delta['player_id'])->update([
                'ranking_points' => $delta['elo_after'],
            ]);

            // Atualizar pivot game_players com snapshot ELO
            DB::table('game_players')
                ->where('game_id', $game->id)
                ->where('player_id', $delta['player_id'])
                ->update([
                    'elo_before' => $delta['elo_before'],
                    'elo_after'  => $delta['elo_after'],
                    'elo_delta'  => $delta['delta'],
                ]);

            // Atualizar player_stats com contadores de ranking
            $stat = PlayerStat::firstOrCreate(
                ['player_id' => $delta['player_id']],
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

            $stat->ranking_matches++;
            $stat->average_elo = $delta['elo_after'];
            $stat->current_streak = $this->computeNewStreak($stat->current_streak, $isWinner);

            if ($isWinner) {
                $stat->ranking_wins++;
                if ($stat->current_streak > $stat->longest_streak) {
                    $stat->longest_streak = $stat->current_streak;
                }
            } else {
                $stat->ranking_losses++;
            }

            $stat->save();
        }
    }

    /**
     * Recalcula a posição de ranking de todos os jogadores ativos usando window function.
     * Executa em uma única query UPDATE para performance.
     */
    public function recalculateAllPositions(): void
    {
        DB::statement("
            UPDATE players
            SET ranking_position = sub.pos
            FROM (
                SELECT id, RANK() OVER (ORDER BY ranking_points DESC) AS pos
                FROM players
                WHERE is_active = true
            ) AS sub
            WHERE players.id = sub.id
        ");

        // Limpar posição de jogadores inativos
        DB::statement("UPDATE players SET ranking_position = NULL WHERE is_active = false");
    }

    /**
     * Recalcula o ranking do clube que sediou a partida e reposiciona todos os clubes.
     */
    public function recalculateClubRanking(int $clubId): void
    {
        DB::statement("
            INSERT INTO club_rankings (club_id, average_elo, active_players, total_ranking_games, win_rate, last_computed_at, created_at, updated_at)
            SELECT
                g.club_id,
                ROUND(AVG(p.ranking_points)::numeric, 2),
                COUNT(DISTINCT p.id),
                COUNT(DISTINCT g.id),
                ROUND(
                    SUM(CASE WHEN gp.team = g.winner_team THEN 1 ELSE 0 END) * 100.0
                    / NULLIF(COUNT(gp.id), 0),
                    2
                ),
                NOW(),
                NOW(),
                NOW()
            FROM games g
            JOIN game_players gp ON gp.game_id = g.id
            JOIN players p ON p.id = gp.player_id
            WHERE g.club_id = :clubId
              AND g.game_type = 'ranking'
              AND g.status = 'completed'
            GROUP BY g.club_id
            ON CONFLICT (club_id) DO UPDATE SET
                average_elo          = EXCLUDED.average_elo,
                active_players       = EXCLUDED.active_players,
                total_ranking_games  = EXCLUDED.total_ranking_games,
                win_rate             = EXCLUDED.win_rate,
                last_computed_at     = EXCLUDED.last_computed_at,
                updated_at           = EXCLUDED.updated_at
        ", ['clubId' => $clubId]);

        // Reposicionar todos os clubes
        DB::statement("
            UPDATE club_rankings
            SET ranking_position = sub.pos
            FROM (
                SELECT id, RANK() OVER (ORDER BY average_elo DESC) AS pos
                FROM club_rankings
            ) AS sub
            WHERE club_rankings.id = sub.id
        ");
    }

    /**
     * Calcula o novo valor de streak.
     * Positivo = sequência de vitórias, negativo = sequência de derrotas.
     *
     * Correção do bug original: min(0, current_streak) - 1 resultava sempre negativo.
     */
    public function computeNewStreak(int $currentStreak, bool $isWinner): int
    {
        if ($isWinner) {
            // Se estava em sequência negativa (derrotas), zera antes de começar sequência positiva
            return ($currentStreak < 0 ? 0 : $currentStreak) + 1;
        }

        // Se estava em sequência positiva (vitórias), zera antes de começar sequência negativa
        return ($currentStreak > 0 ? 0 : $currentStreak) - 1;
    }

    /**
     * Retorna o ELO atual do player naquele clube (1000 se ainda não tem registro).
     */
    private function getClubElo(Player $player, int $clubId): float
    {
        return (float) (ClubPlayerRanking::where('player_id', $player->id)
            ->where('club_id', $clubId)
            ->value('club_elo') ?? 1000);
    }

    /**
     * K-factor baseado no nível do player e nas partidas jogadas naquele clube (cold-start por clube).
     */
    private function getClubKFactor(Player $player, int $clubId): int
    {
        $baseK = match (true) {
            $player->level >= 9 => 20,
            $player->level >= 7 => 24,
            $player->level >= 4 => 30,
            default             => 40,
        };

        $matchesAtClub = ClubPlayerRanking::where('player_id', $player->id)
            ->where('club_id', $clubId)
            ->value('ranking_matches_at_club') ?? 0;

        if ($matchesAtClub < 10) {
            return (int) round($baseK * 1.5);
        }

        return $baseK;
    }

    /**
     * Calcula os deltas ELO por clube para um time, usando o club_elo de cada player naquele clube.
     */
    public function calculateClubEloDeltas(Collection $teamPlayers, float $opponentAvgClubElo, bool $isWinner, int $clubId): array
    {
        $result = [];

        foreach ($teamPlayers as $player) {
            $eloBefore = $this->getClubElo($player, $clubId);
            $k         = $this->getClubKFactor($player, $clubId);

            $expected = 1 / (1 + pow(10, ($opponentAvgClubElo - $eloBefore) / 400));
            $score    = $isWinner ? 1.0 : 0.0;

            $rawDelta = (int) round($k * ($score - $expected));
            $eloAfter = max(100, $eloBefore + $rawDelta);

            $result[] = [
                'player_id'  => $player->id,
                'elo_before' => $eloBefore,
                'elo_after'  => $eloAfter,
                'delta'      => (int) ($eloAfter - $eloBefore),
            ];
        }

        return $result;
    }

    /**
     * Aplica os deltas ELO por clube: atualiza club_player_rankings com ELO, contadores e win_rate.
     */
    private function applyClubDeltas(array $deltas, int $clubId, bool $isWinner): void
    {
        foreach ($deltas as $delta) {
            $stat = ClubPlayerRanking::firstOrCreate(
                ['club_id' => $clubId, 'player_id' => $delta['player_id']],
                [
                    'club_elo'                => 1000,
                    'ranking_matches_at_club' => 0,
                    'ranking_wins_at_club'    => 0,
                    'ranking_losses_at_club'  => 0,
                    'win_rate_at_club'        => 0,
                ]
            );

            $stat->club_elo = $delta['elo_after'];
            $stat->ranking_matches_at_club++;

            if ($isWinner) {
                $stat->ranking_wins_at_club++;
            } else {
                $stat->ranking_losses_at_club++;
            }

            $stat->win_rate_at_club = round(
                $stat->ranking_wins_at_club * 100.0 / $stat->ranking_matches_at_club,
                2
            );
            $stat->last_computed_at = now();
            $stat->save();
        }
    }

    /**
     * Recalcula a posição de todos os players dentro do clube, ordenados por club_elo.
     * Apenas players com >= 3 partidas de ranking no clube recebem uma posição.
     */
    public function recalculateClubPlayerPositions(int $clubId): void
    {
        DB::statement("
            UPDATE club_player_rankings cpr
            SET club_position = sub.pos
            FROM (
                SELECT cpr2.id,
                       RANK() OVER (ORDER BY cpr2.club_elo DESC) AS pos
                FROM club_player_rankings cpr2
                JOIN players p ON p.id = cpr2.player_id
                WHERE cpr2.club_id = :clubId
                  AND p.is_active = true
                  AND cpr2.ranking_matches_at_club >= 3
            ) AS sub
            WHERE cpr.id = sub.id
        ", ['clubId' => $clubId]);

        // Limpar posição de players que não atingiram o mínimo de 3 partidas
        DB::statement("
            UPDATE club_player_rankings
            SET club_position = NULL
            WHERE club_id = :clubId
              AND ranking_matches_at_club < 3
        ", ['clubId' => $clubId]);
    }
}
