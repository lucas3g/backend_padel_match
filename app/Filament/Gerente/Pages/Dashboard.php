<?php

namespace App\Filament\Gerente\Pages;

use App\Filament\Gerente\Widgets\ClubRankingWidget;
use App\Filament\Gerente\Widgets\ClubStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/';

    public function getTitle(): string
    {
        $clube = auth()->user()?->club;
        return $clube ? "Clube: {$clube->name}" : 'Dashboard';
    }

    public function getWidgets(): array
    {
        return [
            ClubStatsWidget::class,
            ClubRankingWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
