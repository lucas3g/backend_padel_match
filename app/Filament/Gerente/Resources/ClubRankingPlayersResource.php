<?php

namespace App\Filament\Gerente\Resources;

use App\Filament\Gerente\Resources\ClubRankingPlayersResource\Pages;
use App\Models\ClubPlayerRanking;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClubRankingPlayersResource extends Resource
{
    protected static ?string $model = ClubPlayerRanking::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationLabel = 'Ranking';
    protected static ?string $modelLabel = 'Posição no Ranking';
    protected static ?string $pluralModelLabel = 'Ranking do Clube';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('player')
            ->where('club_id', auth()->user()->club_id)
            ->whereNotNull('club_position');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('club_position')
                    ->label('#')
                    ->color(fn (mixed $state): string => match (true) {
                        (int) $state === 1 => 'warning',
                        (int) $state === 2 => 'gray',
                        (int) $state === 3 => 'danger',
                        default            => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('player.full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('player.level')
                    ->label('Nível')
                    ->sortable(),

                Tables\Columns\TextColumn::make('player.side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
                        default => '-',
                    }),

                Tables\Columns\TextColumn::make('club_elo')
                    ->label('ELO')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ranking_matches_at_club')
                    ->label('Partidas')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ranking_wins_at_club')
                    ->label('Vitórias')
                    ->numeric(),

                Tables\Columns\BadgeColumn::make('win_rate_at_club')
                    ->label('Win Rate')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 1) . '%')
                    ->color(fn (mixed $state): string => (float) $state >= 50 ? 'success' : 'danger'),
            ])
            ->defaultSort('club_position', 'asc')
            ->filters([
                Filter::make('nivel')
                    ->label('Nível')
                    ->form([
                        TextInput::make('nivel')
                            ->label('Nível do jogador')
                            ->numeric()
                            ->placeholder('ex: 5'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        filled($data['nivel'])
                            ? $query->whereHas('player', fn (Builder $q) => $q->where('level', $data['nivel']))
                            : $query
                    ),

                Tables\Filters\SelectFilter::make('lado')
                    ->label('Lado')
                    ->options([
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $data['value']
                            ? $query->whereHas('player', fn (Builder $q) => $q->where('side', $data['value']))
                            : $query
                    ),

                Filter::make('min_partidas')
                    ->label('Mín. Partidas no Clube')
                    ->form([
                        TextInput::make('min_partidas')
                            ->label('Mínimo de partidas ranking no clube')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('ex: 5'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        filled($data['min_partidas'])
                            ? $query->where('ranking_matches_at_club', '>=', (int) $data['min_partidas'])
                            : $query
                    ),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubRankingPlayers::route('/'),
        ];
    }
}
