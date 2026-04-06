<?php

namespace App\Filament\Painel\Pages;

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
        return [];
    }
}
