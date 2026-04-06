<?php

namespace App\Filament\Painel\Resources\PlayerProfileResource\Pages;

use App\Filament\Painel\Resources\PlayerProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlayerProfile extends EditRecord
{
    protected static string $resource = PlayerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
