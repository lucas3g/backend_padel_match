<?php

namespace App\Filament\Gerente\Resources;

use App\Filament\Gerente\Resources\TopPlayersResource\Pages;
use App\Models\Player;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopPlayersResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Top Jogadores';
    protected static ?string $modelLabel = 'Jogador';
    protected static ?string $pluralModelLabel = 'Top Jogadores';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        $clubId = auth()->user()->club_id;

        return parent::getEloquentQuery()
            ->whereHas('games', fn (Builder $q) => $q->where('games.club_id', $clubId));
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

                Tables\Columns\TextColumn::make('organized_count')
                    ->label('Organizou')
                    ->numeric()
                    ->sortable()
                    ->description('partidas organizadas no clube'),

                Tables\Columns\TextColumn::make('played_count')
                    ->label('Jogou')
                    ->numeric()
                    ->sortable()
                    ->description('partidas jogadas no clube'),

                Tables\Columns\TextColumn::make('total_matches')
                    ->label('Total Geral')
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

                Filter::make('periodo')
                    ->label('Período')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            DatePicker::make('from')
                                ->label('De')
                                ->displayFormat('d/m/Y')
                                ->native(false),
                            DatePicker::make('until')
                                ->label('Até')
                                ->displayFormat('d/m/Y')
                                ->native(false),
                        ]),
                    ])
                    ->query(fn (Builder $query): Builder => $query)
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (filled($data['from'])) {
                            $indicators[] = 'De: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if (filled($data['until'])) {
                            $indicators[] = 'Até: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTopPlayers::route('/'),
        ];
    }
}
