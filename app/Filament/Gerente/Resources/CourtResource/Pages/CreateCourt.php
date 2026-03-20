<?php

namespace App\Filament\Gerente\Resources\CourtResource\Pages;

use App\Filament\Gerente\Resources\CourtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourt extends CreateRecord
{
    protected static string $resource = CourtResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['club_id'] = auth()->user()->club_id;
        return $data;
    }
}
