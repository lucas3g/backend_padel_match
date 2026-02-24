<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubResource\Pages;
use App\Models\Club;
use App\Models\Municipio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Clubes';
    protected static ?string $modelLabel = 'Clube';
    protected static ?string $pluralModelLabel = 'Clubes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informações Básicas')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(200),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(700)
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Contato')->schema([
                Forms\Components\TextInput::make('document')
                    ->label('CNPJ/CPF')
                    ->maxLength(20),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(150),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->maxLength(15),
                Forms\Components\TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->maxLength(15),
            ])->columns(2),

            Forms\Components\Section::make('Endereço')->schema([
                Forms\Components\Select::make('state')
                    ->label('Estado (UF)')
                    ->options([
                        'AC' => 'AC - Acre',
                        'AL' => 'AL - Alagoas',
                        'AP' => 'AP - Amapá',
                        'AM' => 'AM - Amazonas',
                        'BA' => 'BA - Bahia',
                        'CE' => 'CE - Ceará',
                        'DF' => 'DF - Distrito Federal',
                        'ES' => 'ES - Espírito Santo',
                        'GO' => 'GO - Goiás',
                        'MA' => 'MA - Maranhão',
                        'MT' => 'MT - Mato Grosso',
                        'MS' => 'MS - Mato Grosso do Sul',
                        'MG' => 'MG - Minas Gerais',
                        'PA' => 'PA - Pará',
                        'PB' => 'PB - Paraíba',
                        'PR' => 'PR - Paraná',
                        'PE' => 'PE - Pernambuco',
                        'PI' => 'PI - Piauí',
                        'RJ' => 'RJ - Rio de Janeiro',
                        'RN' => 'RN - Rio Grande do Norte',
                        'RS' => 'RS - Rio Grande do Sul',
                        'RO' => 'RO - Rondônia',
                        'RR' => 'RR - Roraima',
                        'SC' => 'SC - Santa Catarina',
                        'SP' => 'SP - São Paulo',
                        'SE' => 'SE - Sergipe',
                        'TO' => 'TO - Tocantins',
                    ])
                    ->live(),
                Forms\Components\Select::make('city')
                    ->label('Município')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search, Forms\Get $get) {
                        return Municipio::when($get('state'), fn ($q, $uf) => $q->where('uf', $uf))
                            ->where('descricao', 'like', "%{$search}%")
                            ->orderBy('descricao')
                            ->limit(50)
                            ->pluck('descricao', 'codigo_ibge')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) =>
                        Municipio::where('codigo_ibge', $value)->value('descricao') ?? $value
                    )
                    ->disabled(fn (Forms\Get $get) => blank($get('state')))
                    ->placeholder(fn (Forms\Get $get) => blank($get('state')) ? 'Selecione o estado primeiro' : 'Digite para buscar o município'),
                Forms\Components\TextInput::make('address')
                    ->label('Endereço')
                    ->maxLength(255),
                Forms\Components\TextInput::make('neighborhood')
                    ->label('Bairro')
                    ->maxLength(50),
                Forms\Components\TextInput::make('zip_code')
                    ->label('CEP')
                    ->maxLength(20),
                Forms\Components\TextInput::make('number')
                    ->label('Número')
                    ->maxLength(10),
                Forms\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric(),
                Forms\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric(),
            ])->columns(2),

            Forms\Components\Section::make('Funcionamento')->schema([
                Forms\Components\TextInput::make('open_time')
                    ->label('Abertura')
                    ->placeholder('08:00'),
                Forms\Components\TextInput::make('close_time')
                    ->label('Fechamento')
                    ->placeholder('22:00'),
                Forms\Components\Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
            ])->columns(3),
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
                Tables\Columns\TextColumn::make('municipio.descricao')
                    ->label('Município')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('UF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_time')
                    ->label('Abertura'),
                Tables\Columns\TextColumn::make('close_time')
                    ->label('Fechamento'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Status')
                    ->trueLabel('Apenas ativos')
                    ->falseLabel('Apenas inativos'),
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
            'index'  => Pages\ListClubs::route('/'),
            'create' => Pages\CreateClub::route('/create'),
            'edit'   => Pages\EditClub::route('/{record}/edit'),
        ];
    }
}
