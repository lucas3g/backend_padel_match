<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayerResource\Pages;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Jogadores';
    protected static ?string $modelLabel = 'Jogador';
    protected static ?string $pluralModelLabel = 'Jogadores';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Jogador')->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('Nome Completo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Select::make('level')
                    ->label('Nível')
                    ->options([
                        'iniciante'    => 'Iniciante',
                        'intermediario' => 'Intermediário',
                        'avancado'     => 'Avançado',
                    ])
                    ->required(),
                Forms\Components\Select::make('side')
                    ->label('Lado')
                    ->options([
                        'direita' => 'Direita',
                        'esquerda' => 'Esquerda',
                        'ambos'   => 'Ambos',
                    ]),
                Forms\Components\Textarea::make('bio')
                    ->label('Bio')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Status')->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativo'),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Verificado'),
            ])->columns(2),

            Forms\Components\Section::make('Estatísticas')->schema([
                Forms\Components\TextInput::make('total_matches')
                    ->label('Total de Partidas')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                Forms\Components\TextInput::make('wins')
                    ->label('Vitórias')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                Forms\Components\TextInput::make('losses')
                    ->label('Derrotas')
                    ->numeric()
                    ->default(0)
                    ->disabled(),
                Forms\Components\TextInput::make('ranking_points')
                    ->label('Pontos de Ranking')
                    ->numeric()
                    ->default(1000)
                    ->disabled(),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('level')
                    ->label('Nível')
                    ->colors([
                        'success' => 'iniciante',
                        'warning' => 'intermediario',
                        'danger'  => 'avancado',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'iniciante'    => 'Iniciante',
                        'intermediario' => 'Intermediário',
                        'avancado'     => 'Avançado',
                        default        => $state,
                    }),
                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'direita'  => 'Direita',
                        'esquerda' => 'Esquerda',
                        'ambos'    => 'Ambos',
                        default    => $state ?? '-',
                    }),
                Tables\Columns\TextColumn::make('total_matches')
                    ->label('Partidas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wins')
                    ->label('Vitórias')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ranking_points')
                    ->label('Pontos')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verificado')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Nível')
                    ->options([
                        'iniciante'    => 'Iniciante',
                        'intermediario' => 'Intermediário',
                        'avancado'     => 'Avançado',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'edit'   => Pages\EditPlayer::route('/{record}/edit'),
        ];
    }
}
