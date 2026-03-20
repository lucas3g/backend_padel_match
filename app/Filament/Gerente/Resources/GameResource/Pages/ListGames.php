<?php

namespace App\Filament\Gerente\Resources\GameResource\Pages;

use App\Filament\Gerente\Resources\GameResource;
use Filament\Resources\Pages\ListRecords;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
