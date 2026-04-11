<?php

namespace App\Filament\Painel\Resources;

use App\Filament\Painel\Resources\ClubResource\Pages;
use App\Models\Club;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Clubes';
    protected static ?string $modelLabel = 'Clube';
    protected static ?string $pluralModelLabel = 'Clubes';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $playerId = auth()->user()?->player?->id ?? 0;

        return parent::getEloquentQuery()
            ->where('clubs.active', true)
            ->select('clubs.*')
            ->selectRaw(
                'EXISTS(SELECT 1 FROM player_favorite_clubs WHERE player_id = ? AND club_id = clubs.id) as is_favorite',
                [$playerId]
            );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Clube')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('Localização')
                    ->formatStateUsing(function ($state, Club $record): string {
                        return collect([$state, $record->state])
                            ->filter()
                            ->implode(' / ');
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('open_time')
                    ->label('Horário')
                    ->formatStateUsing(function ($state, Club $record): string {
                        $open  = $state ? substr($state, 0, 5) : null;
                        $close = $record->close_time ? substr($record->close_time, 0, 5) : null;

                        if ($open && $close) {
                            return "{$open} – {$close}";
                        }

                        return $open ?? '–';
                    }),

                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('Favorito')
                    ->getStateUsing(fn (Club $record): bool => (bool) $record->is_favorite)
                    ->boolean()
                    ->trueIcon('heroicon-s-heart')
                    ->falseIcon('heroicon-o-heart')
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_favorite')
                    ->label(fn (Club $record): string => $record->is_favorite
                        ? 'Remover dos favoritos'
                        : 'Favoritar')
                    ->icon(fn (Club $record): string => $record->is_favorite
                        ? 'heroicon-s-heart'
                        : 'heroicon-o-heart')
                    ->color(fn (Club $record): string => $record->is_favorite
                        ? 'danger'
                        : 'gray')
                    ->action(function (Club $record): void {
                        $player = auth()->user()?->player;

                        if (! $player) {
                            return;
                        }

                        $player->favoriteClubs()->toggle($record->id);
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('apenas_favoritos')
                    ->label('Apenas favoritos')
                    ->query(function (Builder $query): Builder {
                        $playerId = auth()->user()?->player?->id ?? 0;

                        return $query->whereExists(function ($sub) use ($playerId) {
                            $sub->select(DB::raw(1))
                                ->from('player_favorite_clubs')
                                ->whereColumn('club_id', 'clubs.id')
                                ->where('player_id', $playerId);
                        });
                    }),
            ])
            ->defaultSort('name', 'asc')
            ->headerActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubs::route('/'),
        ];
    }
}
