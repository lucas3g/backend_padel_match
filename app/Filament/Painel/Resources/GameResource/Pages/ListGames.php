<?php

namespace App\Filament\Painel\Resources\GameResource\Pages;

use App\Filament\Painel\Resources\GameResource;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
