<?php

namespace App\Filament\Gerente\Resources;

use App\Filament\Gerente\Resources\PlayerResource\Pages;
use App\Models\Player;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Jogadores';
    protected static ?string $modelLabel = 'Jogador';
    protected static ?string $pluralModelLabel = 'Jogadores';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        $clubId = auth()->user()->club_id;

        return parent::getEloquentQuery()
            ->whereHas('games', fn (Builder $q) =>
                $q->where('games.club_id', $clubId)
            )
            ->distinct();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('Nível')
                    ->sortable(),
                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
                        default => '-',
                    }),
                Tables\Columns\TextColumn::make('uf')
                    ->label('UF'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_matches')
                    ->label('Partidas')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('side')
                    ->label('Lado')
                    ->options([
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
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
            'index' => Pages\ListPlayers::route('/'),
        ];
    }
}
