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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['disponibilidade'] ?? 'disponivel') === 'disponivel') {
            $data['motivo_indisponibilidade'] = null;
            $data['disponivel_ate'] = null;
        }

        return $data;
    }
}
