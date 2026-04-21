<?php

namespace App\Filament\Gerente\Resources\TopPlayersResource\Pages;

use App\Filament\Gerente\Resources\TopPlayersResource;
use App\Models\Player;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTopPlayers extends ListRecords
{
    protected static string $resource = TopPlayersResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getTableQuery(): Builder
    {
        $clubId = auth()->user()->club_id;
        $from   = $this->tableFilters['periodo']['from']  ?? null;
        $until  = $this->tableFilters['periodo']['until'] ?? null;

        return Player::query()
            ->whereHas('games', fn (Builder $q) => $q->where('games.club_id', $clubId))
            ->withCount([
                'ownedGames as organized_count' => function ($q) use ($clubId, $from, $until) {
                    $q->where('club_id', $clubId)->where('status', 'completed');
                    if (filled($from))  $q->whereDate('data_time', '>=', $from);
                    if (filled($until)) $q->whereDate('data_time', '<=', $until);
                },
                'games as played_count' => function ($q) use ($clubId, $from, $until) {
                    $q->where('games.club_id', $clubId)->where('games.status', 'completed');
                    if (filled($from))  $q->whereDate('games.data_time', '>=', $from);
                    if (filled($until)) $q->whereDate('games.data_time', '<=', $until);
                },
            ]);
    }

    public function getTabs(): array
    {
        return [
            'organizadores' => Tab::make('Mais Organizadores')
                ->icon('heroicon-o-clipboard-document-list')
                ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('organized_count')),

            'ativos' => Tab::make('Mais Ativos')
                ->icon('heroicon-o-fire')
                ->modifyQueryUsing(fn (Builder $query) => $query->orderByDesc('played_count')),
        ];
    }
}
