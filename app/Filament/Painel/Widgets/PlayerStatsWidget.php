<?php

namespace App\Filament\Painel\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlayerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $player = auth()->user()?->player;

        if (! $player) {
            return [];
        }

        $stats = $player->stats;

        $totalMatches    = $stats?->total_matches    ?? 0;
        $wins            = $stats?->wins             ?? 0;
        $losses          = $stats?->losses           ?? 0;
        $winRate         = $stats?->win_rate          ?? 0.0;
        $currentStreak   = $stats?->current_streak   ?? 0;
        $longestStreak   = $stats?->longest_streak   ?? 0;
        $averageElo      = $stats?->average_elo      ?? 0;
        $rankingMatches  = $stats?->ranking_matches  ?? 0;
        $rankingWins     = $stats?->ranking_wins     ?? 0;
        $rankingLosses   = $stats?->ranking_losses   ?? 0;

        $streakValue = $currentStreak > 0 ? "+{$currentStreak}" : (string) $currentStreak;
        $streakColor = $currentStreak > 0 ? 'success' : ($currentStreak < 0 ? 'danger' : 'gray');

        $rankingWinRate = $rankingMatches > 0
            ? number_format(($rankingWins / $rankingMatches) * 100, 1) . '%'
            : '—';

        return [
            Stat::make('Partidas', (string) $totalMatches)
                ->description("{$wins}V / {$losses}D")
                ->icon('heroicon-o-play-circle')
                ->color('primary'),

            Stat::make('Taxa de Vitória', number_format((float) $winRate, 1) . '%')
                ->description('% de vitórias')
                ->icon('heroicon-o-chart-bar')
                ->color((float) $winRate >= 50 ? 'success' : 'danger'),

            Stat::make('Sequência Atual', $streakValue)
                ->description("Maior sequência: {$longestStreak}")
                ->icon('heroicon-o-fire')
                ->color($streakColor),

            Stat::make('ELO Médio', number_format((float) $averageElo, 0))
                ->description('Pontuação ELO média')
                ->icon('heroicon-o-star')
                ->color('info'),

            Stat::make('Partidas Ranking', (string) $rankingMatches)
                ->description("{$rankingWins}V / {$rankingLosses}D — Taxa: {$rankingWinRate}")
                ->icon('heroicon-o-trophy')
                ->color('warning'),
        ];
    }
}
