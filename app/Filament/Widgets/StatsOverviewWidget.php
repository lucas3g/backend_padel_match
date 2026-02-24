<?php

namespace App\Filament\Widgets;

use App\Models\Club;
use App\Models\Game;
use App\Models\Player;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Clubes Ativos', Club::where('active', true)->count())
                ->description('Total de clubes cadastrados')
                ->icon('heroicon-o-building-office-2')
                ->color('success'),

            Stat::make('Partidas Abertas', Game::where('status', 'open')->count())
                ->description('Aguardando jogadores')
                ->icon('heroicon-o-play-circle')
                ->color('warning'),

            Stat::make('Jogadores Ativos', Player::where('is_active', true)->count())
                ->description('Total de jogadores na plataforma')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Partidas Hoje', Game::whereDate('data_time', today())->count())
                ->description('Agendadas para hoje')
                ->icon('heroicon-o-calendar')
                ->color('info'),
        ];
    }
}
