<?php

namespace App\Filament\Painel\Resources;

use App\Filament\Painel\Resources\PlayerProfileResource\Pages;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlayerProfileResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Meu Perfil';
    protected static ?string $modelLabel = 'Perfil';
    protected static ?string $pluralModelLabel = 'Perfil';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Perfil')
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->label('Nome completo')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefone')
                        ->maxLength(20),
                    Forms\Components\Select::make('level')
                        ->label('Categoria')
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
                    Forms\Components\Textarea::make('bio')
                        ->label('Bio')
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Disponibilidade')
                ->description('Informe sua disponibilidade atual para que outros jogadores saibam se você pode jogar.')
                ->schema([
                    Forms\Components\Select::make('disponibilidade')
                        ->label('Status')
                        ->options([
                            'disponivel' => 'Disponível',
                            'machucado'  => 'Machucado',
                            'viajando'   => 'Viajando',
                            'licenca'    => 'De licença',
                        ])
                        ->default('disponivel')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('motivo_indisponibilidade')
                        ->label('Motivo')
                        ->placeholder('Descreva brevemente o motivo da indisponibilidade...')
                        ->maxLength(500)
                        ->rows(3)
                        ->visible(fn (Get $get): bool => $get('disponibilidade') !== 'disponivel' && $get('disponibilidade') !== null)
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('disponivel_ate')
                        ->label('Disponível a partir de')
                        ->placeholder('Data prevista de retorno')
                        ->minDate(now()->addDay())
                        ->displayFormat('d/m/Y')
                        ->visible(fn (Get $get): bool => $get('disponibilidade') !== 'disponivel' && $get('disponibilidade') !== null)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('level')
                    ->label('Categoria')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => '1 - Pro',
                        2 => '2 - Avançado+',
                        3 => '3 - Avançado',
                        4 => '4 - Intermediário+',
                        5 => '5 - Intermediário',
                        6 => '6 - Iniciante+',
                        7 => '7 - Iniciante',
                        default => "Nível {$state}",
                    }),
                Tables\Columns\TextColumn::make('ranking_points')
                    ->label('Pontos')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_matches')
                    ->label('Partidas')
                    ->numeric(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayerProfiles::route('/'),
            'edit'  => Pages\EditPlayerProfile::route('/{record}/edit'),
        ];
    }
}
