<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Partidas';
    protected static ?string $modelLabel = 'Partida';
    protected static ?string $pluralModelLabel = 'Partidas';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informações da Partida')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'public'  => 'Pública',
                        'private' => 'Privada',
                    ])
                    ->required()
                    ->default('public'),
                Forms\Components\Select::make('game_type')
                    ->label('Modalidade')
                    ->options([
                        'casual'      => 'Casual',
                        'competitive' => 'Competitivo',
                        'training'    => 'Treino',
                        'ranking'     => 'Ranking',
                    ])
                    ->default('casual'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'open'        => 'Aberta',
                        'full'        => 'Cheia',
                        'in_progress' => 'Em andamento',
                        'completed'   => 'Concluída',
                        'canceled'    => 'Cancelada',
                    ])
                    ->required()
                    ->default('open'),
                Forms\Components\DateTimePicker::make('data_time')
                    ->label('Data e Hora'),
                Forms\Components\TextInput::make('max_players')
                    ->label('Máx. de Jogadores')
                    ->numeric()
                    ->default(4),
                Forms\Components\TextInput::make('duration_minutes')
                    ->label('Duração (min)')
                    ->numeric()
                    ->default(90),
            ])->columns(2),

            Forms\Components\Section::make('Resultado')->schema([
                Forms\Components\TextInput::make('team1_score')
                    ->label('Placar Time 1')
                    ->numeric(),
                Forms\Components\TextInput::make('team2_score')
                    ->label('Placar Time 2')
                    ->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'public',
                        'warning' => 'private',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'public'  => 'Pública',
                        'private' => 'Privada',
                        default   => $state,
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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open'        => 'Aberta',
                        'full'        => 'Cheia',
                        'in_progress' => 'Em andamento',
                        'completed'   => 'Concluída',
                        'canceled'    => 'Cancelada',
                        default       => $state,
                    }),
                Tables\Columns\BadgeColumn::make('game_type')
                    ->label('Modalidade')
                    ->colors([
                        'gray'    => 'casual',
                        'info'    => 'training',
                        'danger'  => 'competitive',
                        'warning' => 'ranking',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'casual'      => 'Casual',
                        'competitive' => 'Competitivo',
                        'training'    => 'Treino',
                        'ranking'     => 'Ranking',
                        default       => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('owner.full_name')
                    ->label('Dono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_time')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_players')
                    ->label('Vagas')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
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
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'public'  => 'Pública',
                        'private' => 'Privada',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit'   => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
