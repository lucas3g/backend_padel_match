<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Ranking nos Clubes Favoritos</x-slot>
        <x-slot name="headerEnd">
            <x-filament::icon
                icon="heroicon-o-trophy"
                class="h-5 w-5 text-warning-500"
            />
        </x-slot>

        @if (empty($clubes))
            <p class="text-sm text-gray-400 dark:text-gray-500">Nenhum clube favorito adicionado.</p>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($clubes as $clube)
                    @php
                        $posicao   = $clube['club_position'] ? "#{$clube['club_position']}" : '—';
                        $elo       = $clube['club_elo'] ?? '—';
                        $partidas  = $clube['ranking_matches_at_club'];
                        $vitorias  = $clube['ranking_wins_at_club'];
                        $derrotas  = $clube['ranking_losses_at_club'];
                        $taxa      = $clube['win_rate_at_club'] !== null
                            ? number_format($clube['win_rate_at_club'], 1) . '%'
                            : '—';
                        $ranqueado = $clube['club_position'] !== null;
                    @endphp

                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {{-- Nome do clube --}}
                        <div class="mb-3 flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-o-building-office-2"
                                class="h-5 w-5 text-primary-500 shrink-0"
                            />
                            <span class="truncate font-semibold text-gray-800 dark:text-gray-100">
                                {{ $clube['club_name'] }}
                            </span>
                        </div>

                        {{-- Posição e ELO --}}
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-center">
                                <p class="text-2xl font-bold {{ $ranqueado ? 'text-warning-500' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ $posicao }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Posição</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ $elo }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ELO</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                                    {{ $taxa }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Taxa V.</p>
                            </div>
                        </div>

                        {{-- Partidas / Vitórias / Derrotas --}}
                        <div class="flex justify-around rounded-lg bg-gray-50 px-2 py-2 dark:bg-gray-700">
                            <div class="text-center">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $partidas }}</p>
                                <p class="text-xs text-gray-400">Partidas</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-success-600 dark:text-success-400">{{ $vitorias }}</p>
                                <p class="text-xs text-gray-400">Vitórias</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $derrotas }}</p>
                                <p class="text-xs text-gray-400">Derrotas</p>
                            </div>
                        </div>

                        @if (! $ranqueado && $partidas === 0)
                            <p class="mt-2 text-center text-xs text-gray-400 dark:text-gray-500">
                                Nenhuma partida de ranking neste clube ainda.
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
