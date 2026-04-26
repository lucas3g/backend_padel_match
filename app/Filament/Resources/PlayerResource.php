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
                        1 => '1 - Pro',
                        2 => '2 - Avançado+',
                        3 => '3 - Avançado',
                        4 => '4 - Intermediário+',
                        5 => '5 - Intermediário',
                        6 => '6 - Iniciante+',
                        7 => '7 - Iniciante',
                    ])
                    ->required(),
                Forms\Components\Select::make('side')
                    ->label('Lado')
                    ->options([
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
                    ]),
                Forms\Components\Select::make('sexo')
                    ->label('Sexo')
                    ->options([
                        'masculino'            => 'Masculino',
                        'feminino'             => 'Feminino',
                        'prefiro_nao_informar' => 'Prefiro não informar',
                    ])
                    ->placeholder('Não informado'),
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
                Tables\Columns\TextColumn::make('level')
                    ->label('Nível')
                    ->badge()
                    ->color(fn ($state): string => match ((int) $state) {
                        1, 2    => 'danger',
                        3, 4    => 'warning',
                        5, 6    => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ((int) $state) {
                        1 => '1 - Pro',
                        2 => '2 - Avançado+',
                        3 => '3 - Avançado',
                        4 => '4 - Intermediário+',
                        5 => '5 - Intermediário',
                        6 => '6 - Iniciante+',
                        7 => '7 - Iniciante',
                        default => "Nível {$state}",
                    }),
                Tables\Columns\TextColumn::make('side')
                    ->label('Lado')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'left'  => 'Esquerda',
                        'right' => 'Direita',
                        'both'  => 'Ambos',
                        default => $state ?? '-',
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
                        1 => '1 - Pro',
                        2 => '2 - Avançado+',
                        3 => '3 - Avançado',
                        4 => '4 - Intermediário+',
                        5 => '5 - Intermediário',
                        6 => '6 - Iniciante+',
                        7 => '7 - Iniciante',
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
