<?php

namespace App\Filament\Gerente\Widgets;

use App\Models\Court;
use App\Models\Game;
use App\Models\Player;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ClubStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $clubId = auth()->user()->club_id;

        $quadrasAtivas = Court::where('club_id', $clubId)
            ->where('active', true)
            ->count();

        $partidasMes = Game::where('club_id', $clubId)
            ->whereMonth('data_time', now()->month)
            ->whereYear('data_time', now()->year)
            ->count();

        $jogadoresUnicos = Player::whereHas('games', fn ($q) =>
            $q->where('games.club_id', $clubId)
              ->whereMonth('games.data_time', now()->month)
              ->whereYear('games.data_time', now()->year)
        )->count();

        $clube = auth()->user()->club;
        $statusClube = $clube?->active ? 'Ativo' : 'Inativo';
        $statusCor = $clube?->active ? 'success' : 'danger';

        return [
            Stat::make('Quadras Ativas', $quadrasAtivas)
                ->description('Quadras em operação no clube')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary'),

            Stat::make('Partidas este Mês', $partidasMes)
                ->description('Partidas no clube em ' . now()->translatedFormat('F'))
                ->icon('heroicon-o-play-circle')
                ->color('warning'),

            Stat::make('Jogadores Únicos (mês)', $jogadoresUnicos)
                ->description('Jogadores que passaram pelo clube este mês')
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('Status do Clube', $statusClube)
                ->description($clube?->name ?? 'Clube não associado')
                ->icon('heroicon-o-building-office-2')
                ->color($statusCor),
        ];
    }
}
