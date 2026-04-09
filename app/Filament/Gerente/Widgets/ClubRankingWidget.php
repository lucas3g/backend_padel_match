<?php

namespace App\Filament\Gerente\Widgets;

use App\Models\ClubRanking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClubRankingWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Ranking do Clube';

    protected function getStats(): array
    {
        $clubId = auth()->user()?->club_id;

        if (! $clubId) {
            return [];
        }

        $ranking = ClubRanking::where('club_id', $clubId)->first();

        if (! $ranking) {
            return [
                Stat::make('Posição no Ranking', '—')
                    ->description('Nenhuma partida de ranking registrada ainda')
                    ->icon('heroicon-o-trophy')
                    ->color('gray'),
            ];
        }

        $position    = $ranking->ranking_position ? "#{$ranking->ranking_position}" : '—';
        $winRateStr  = number_format((float) $ranking->win_rate, 1) . '%';
        $avgEloStr   = number_format((float) $ranking->average_elo, 0);
        $updatedAt   = $ranking->last_computed_at
            ? $ranking->last_computed_at->format('d/m/Y H:i')
            : null;

        return [
            Stat::make('Posição no Ranking', $position)
                ->description('Ranking global entre clubes')
                ->icon('heroicon-o-trophy')
                ->color('warning'),

            Stat::make('ELO Médio', $avgEloStr)
                ->description("Jogadores ativos: {$ranking->active_players}")
                ->icon('heroicon-o-star')
                ->color('info'),

            Stat::make('Partidas Ranking', (string) $ranking->total_ranking_games)
                ->description("Taxa de vitória: {$winRateStr}")
                ->icon('heroicon-o-chart-bar')
                ->color((float) $ranking->win_rate >= 50 ? 'success' : 'danger'),
        ];
    }
}
