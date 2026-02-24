<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourtResource\Pages;
use App\Models\Club;
use App\Models\Court;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourtResource extends Resource
{
    protected static ?string $model = Court::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Quadras';
    protected static ?string $modelLabel = 'Quadra';
    protected static ?string $pluralModelLabel = 'Quadras';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informações da Quadra')->schema([
                Forms\Components\Select::make('club_id')
                    ->label('Clube')
                    ->options(fn () => Club::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'padel'       => 'Padel',
                        'beach_tenis' => 'Beach Tênis',
                    ])
                    ->required()
                    ->default('padel'),
                Forms\Components\TextInput::make('price_per_hour')
                    ->label('Valor por hora (R$)')
                    ->numeric()
                    ->prefix('R$'),
                Forms\Components\Toggle::make('covered')
                    ->label('Coberta'),
                Forms\Components\Toggle::make('active')
                    ->label('Ativa')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Imagem')->schema([
                Forms\Components\TextInput::make('main_image_url')
                    ->label('URL da Imagem Principal')
                    ->url()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('club.name')
                    ->label('Clube')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'primary' => 'padel',
                        'success' => 'beach_tenis',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'padel'       => 'Padel',
                        'beach_tenis' => 'Beach Tênis',
                        default       => $state,
                    }),
                Tables\Columns\IconColumn::make('covered')
                    ->label('Coberta')
                    ->boolean(),
                Tables\Columns\TextColumn::make('price_per_hour')
                    ->label('Valor/hora')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('club')
                    ->label('Clube')
                    ->relationship('club', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'padel'       => 'Padel',
                        'beach_tenis' => 'Beach Tênis',
                    ]),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Ativa'),
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
            'index'  => Pages\ListCourts::route('/'),
            'create' => Pages\CreateCourt::route('/create'),
            'edit'   => Pages\EditCourt::route('/{record}/edit'),
        ];
    }
}
