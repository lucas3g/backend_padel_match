<?php

namespace App\Filament\Gerente\Resources\ClubResource\Pages;

use App\Filament\Gerente\Resources\ClubResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClub extends EditRecord
{
    protected static string $resource = ClubResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function mount(int | string $record = null): void
    {
        // Se não passar record, usa o clube do usuário logado
        if ($record === null) {
            $record = auth()->user()->club_id;
        }

        parent::mount($record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getNavigationUrl();
    }
}
