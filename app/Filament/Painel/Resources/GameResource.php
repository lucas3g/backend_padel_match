<?php

namespace App\Filament\Painel\Resources;

use App\Filament\Painel\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Minhas Partidas';
    protected static ?string $modelLabel = 'Partida';
    protected static ?string $pluralModelLabel = 'Partidas';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $playerId = auth()->user()?->player?->id;

        return parent::getEloquentQuery()
            ->with('players')
            ->where(function (Builder $q) use ($playerId) {
                $q->where('owner_player_id', $playerId)
                  ->orWhereHas('players', fn (Builder $sub) => $sub->where('players.id', $playerId));
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('court.name')
                    ->label('Quadra')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'public',
                        'warning' => 'private',
                    ])
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'public'  => 'Pública',
                        'private' => 'Privada',
                        default   => $state instanceof \BackedEnum ? $state->value : (string) $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'open',
                        'warning' => 'full',
                        'primary' => 'in_progress',
                        'gray'    => 'completed',
                        'danger'  => 'canceled',
                    ])
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'open'        => 'Aberta',
                        'full'        => 'Cheia',
                        'in_progress' => 'Em andamento',
                        'completed'   => 'Concluída',
                        'canceled'    => 'Cancelada',
                        default       => $state instanceof \BackedEnum ? $state->value : (string) $state,
                    }),
                Tables\Columns\TextColumn::make('data_time')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('resultado')
                    ->label('Placar')
                    ->state(fn (Game $record): string =>
                        !is_null($record->team1_score)
                            ? "{$record->team1_score} × {$record->team2_score}"
                            : '—'
                    )
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('owner.full_name')
                    ->label('Organizador'),
            ])
            ->defaultSort('data_time', 'desc')
            ->actions([
                Tables\Actions\Action::make('detalhes')
                    ->label('Jogadores / Resultado')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->modalHeading(fn (Game $record): string => $record->title ?? 'Detalhes da Partida')
                    ->modalContent(fn (Game $record) => view(
                        'filament.painel.partida-detalhes',
                        ['game' => $record->loadMissing('players')]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open'        => 'Aberta',
                        'full'        => 'Cheia',
                        'in_progress' => 'Em andamento',
                        'completed'   => 'Concluída',
                        'canceled'    => 'Cancelada',
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
        ];
    }
}
