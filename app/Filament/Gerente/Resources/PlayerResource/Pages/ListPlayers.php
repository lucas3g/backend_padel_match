<?php

namespace App\Filament\Gerente\Resources\PlayerResource\Pages;

use App\Filament\Gerente\Resources\PlayerResource;
use Filament\Resources\Pages\ListRecords;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
