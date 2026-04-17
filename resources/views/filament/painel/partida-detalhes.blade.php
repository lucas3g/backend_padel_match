<div class="space-y-4 py-2">

    {{-- Resultado --}}
    @if (!is_null($game->team1_score) && !is_null($game->team2_score))
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Resultado</h3>
            <div class="flex items-center justify-center gap-4 bg-gray-50 dark:bg-gray-800 rounded-lg py-3 px-4">
                <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $game->team1_score }}</span>
                <span class="text-lg text-gray-400">×</span>
                <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $game->team2_score }}</span>
            </div>
            @if ($game->winner_team)
                <p class="text-center text-sm mt-1 text-gray-500">
                    Vencedor: <span class="font-semibold text-green-600">Time {{ $game->winner_team }}</span>
                </p>
            @endif
        </div>
    @else
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Resultado</h3>
            <p class="text-sm text-gray-400 italic">Partida ainda não concluída.</p>
        </div>
    @endif

    {{-- Jogadores --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Jogadores</h3>

        @php
            $grouped = $game->players->groupBy(fn ($p) => $p->pivot->team ?? null);
            $hasTeams = $grouped->keys()->filter()->isNotEmpty();
        @endphp

        @if ($game->players->isEmpty())
            <p class="text-sm text-gray-400 italic">Nenhum jogador inscrito.</p>
        @elseif ($hasTeams)
            <div class="grid grid-cols-2 gap-3">
                @foreach (['1', '2'] as $team)
                    @php $players = $grouped->get($team, collect()); @endphp
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase">Time {{ $team }}</p>
                        @forelse ($players as $player)
                            <div class="flex items-center gap-2 py-1">
                                <div class="w-2 h-2 rounded-full {{ $team === '1' ? 'bg-blue-400' : 'bg-orange-400' }}"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $player->full_name }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Sem jogadores</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-1">
                @foreach ($game->players as $player)
                    <div class="flex items-center gap-2 py-1">
                        <div class="w-2 h-2 rounded-full bg-primary-400"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ $player->full_name }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
