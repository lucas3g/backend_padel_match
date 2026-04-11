<?php

namespace App\Filament\Painel\Resources\ClubResource\Pages;

use App\Filament\Painel\Resources\ClubResource;
use Filament\Resources\Pages\ListRecords;

class ListClubs extends ListRecords
{
    protected static string $resource = ClubResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
