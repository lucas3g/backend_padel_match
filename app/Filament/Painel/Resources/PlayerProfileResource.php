<?php

namespace App\Filament\Painel\Resources;

use App\Filament\Painel\Resources\PlayerProfileResource\Pages;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
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
            Forms\Components\TextInput::make('full_name')
                ->label('Nome completo')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('Telefone')
                ->maxLength(20),
            Forms\Components\Select::make('level')
                ->label('Nível')
                ->options([
                    'iniciante'    => 'Iniciante',
                    'intermediario' => 'Intermediário',
                    'avancado'     => 'Avançado',
                    'profissional' => 'Profissional',
                ]),
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('level')
                    ->label('Nível'),
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
