<?php

namespace App\Filament\Painel\Widgets;

use App\Models\Game;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class MatchHistoryWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Histórico de Partidas';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('data_time', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('data_time')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('game_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'competitive' => 'Competitivo',
                        'training'    => 'Treino',
                        'casual'      => 'Casual',
                        default       => $state ?? '—',
                    })
                    ->colors([
                        'danger' => 'competitive',
                        'info'   => 'training',
                        'gray'   => 'casual',
                    ]),

                Tables\Columns\TextColumn::make('club.name')
                    ->label('Clube / Quadra')
                    ->description(fn (Game $record): ?string => $record->court?->name)
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('resultado')
                    ->label('Resultado')
                    ->getStateUsing(function (Game $record): string {
                        $player  = auth()->user()?->player;
                        $meuTime = $record->players
                            ->firstWhere('id', $player?->id)
                            ?->pivot?->team;

                        if (! $meuTime || ! $record->winner_team) {
                            return 'sem_resultado';
                        }

                        return ((int) $meuTime === (int) $record->winner_team) ? 'vitoria' : 'derrota';
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vitoria'       => 'Vitória',
                        'derrota'       => 'Derrota',
                        'sem_resultado' => '—',
                        default         => $state,
                    })
                    ->colors([
                        'success' => 'vitoria',
                        'danger'  => 'derrota',
                        'gray'    => 'sem_resultado',
                    ]),

                Tables\Columns\TextColumn::make('placar')
                    ->label('Placar')
                    ->getStateUsing(fn (Game $record): string =>
                        ($record->team1_score !== null && $record->team2_score !== null)
                            ? "{$record->team1_score} x {$record->team2_score}"
                            : '—'
                    ),

                Tables\Columns\TextColumn::make('parceiros')
                    ->label('Parceiros')
                    ->getStateUsing(function (Game $record): string {
                        $player  = auth()->user()?->player;
                        $meuTime = $record->players
                            ->firstWhere('id', $player?->id)
                            ?->pivot?->team;

                        if (! $meuTime) {
                            return '—';
                        }

                        return $record->players
                            ->filter(fn ($p) => $p->id !== $player?->id
                                && (int) $p->pivot->team === (int) $meuTime
                                && $p->pivot->team !== null)
                            ->map(fn ($p) => $p->full_name)
                            ->implode(', ') ?: '—';
                    }),

                Tables\Columns\TextColumn::make('adversarios')
                    ->label('Adversários')
                    ->getStateUsing(function (Game $record): string {
                        $player  = auth()->user()?->player;
                        $meuTime = $record->players
                            ->firstWhere('id', $player?->id)
                            ?->pivot?->team;

                        return $record->players
                            ->filter(fn ($p) => $p->pivot->team !== null
                                && (int) $p->pivot->team !== (int) $meuTime)
                            ->map(fn ($p) => $p->full_name)
                            ->implode(', ') ?: '—';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('resultado')
                    ->label('Resultado')
                    ->options([
                        'todos'   => 'Todos',
                        'vitoria' => 'Vitórias',
                        'derrota' => 'Derrotas',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $valor = $data['value'] ?? null;

                        if (! $valor || $valor === 'todos') {
                            return $query;
                        }

                        if ($valor === 'vitoria') {
                            return $query->whereRaw(
                                'game_players.team = games.winner_team AND games.winner_team IS NOT NULL'
                            );
                        }

                        return $query->whereRaw(
                            'game_players.team != games.winner_team AND games.winner_team IS NOT NULL'
                        );
                    }),
            ])
            ->recordUrl(null)
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        $player = auth()->user()?->player;

        return Game::query()
            ->where('games.status', 'completed')
            ->whereHas('players', fn (Builder $q) => $q->where('players.id', $player?->id))
            ->join('game_players', function ($join) use ($player) {
                $join->on('game_players.game_id', '=', 'games.id')
                     ->where('game_players.player_id', '=', $player?->id);
            })
            ->with([
                'players' => fn ($q) => $q->withPivot('team')
                    ->select('players.id', 'players.full_name', 'players.level', 'players.side'),
                'club:id,name',
                'court:id,name',
            ])
            ->select('games.*');
    }
}
