<?php

namespace App\Filament\Painel\Pages;

use App\Filament\Painel\Widgets\MatchHistoryWidget;
use App\Filament\Painel\Widgets\PlayerProfileWidget;
use App\Filament\Painel\Widgets\PlayerStatsWidget;
use App\Filament\Painel\Widgets\RecentResultsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/';

    public function getTitle(): string
    {
        $player = auth()->user()?->player;
        return $player ? "Olá, {$player->full_name}!" : 'Painel do Jogador';
    }

    public function getWidgets(): array
    {
        return [
            PlayerProfileWidget::class,
            PlayerStatsWidget::class,
            RecentResultsWidget::class,
            MatchHistoryWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}
