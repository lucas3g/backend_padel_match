<?php

namespace App\Filament\Gerente\Resources\ClubRankingPlayersResource\Pages;

use App\Filament\Gerente\Resources\ClubRankingPlayersResource;
use Filament\Resources\Pages\ListRecords;

class ListClubRankingPlayers extends ListRecords
{
    protected static string $resource = ClubRankingPlayersResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
