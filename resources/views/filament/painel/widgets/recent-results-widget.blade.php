<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Últimos 5 Resultados</x-slot>

        @if (empty($results))
            <p class="text-sm text-gray-400 dark:text-gray-500">Nenhuma partida concluída ainda.</p>
        @else
            <div class="flex items-center gap-4 flex-wrap">
                @foreach ($results as $result)
                    @php
                        $config = match ($result['resultado']) {
                            'vitoria' => [
                                'label' => 'V',
                                'title' => 'Vitória',
                                'style' => 'background-color: #22c55e; color: #fff;',
                            ],
                            'derrota' => [
                                'label' => 'D',
                                'title' => 'Derrota',
                                'style' => 'background-color: #ef4444; color: #fff;',
                            ],
                            default => [
                                'label' => '—',
                                'title' => 'Sem Resultado',
                                'style' => 'background-color: #d1d5db; color: #374151;',
                            ],
                        };

                        $gameTypeLabel = match ($result['game_type'] ?? 'casual') {
                            'competitive' => 'Competitivo',
                            'training'    => 'Treino',
                            default       => 'Casual',
                        };

                        $date = $result['data_time']
                            ? \Carbon\Carbon::parse($result['data_time'])->format('d/m/Y')
                            : '';
                    @endphp

                    <div
                        class="flex flex-col items-center gap-1"
                        title="{{ $config['title'] }} — {{ $gameTypeLabel }} em {{ $date }}"
                    >
                        <span
                            style="{{ $config['style'] }} width:2.5rem; height:2.5rem; border-radius:9999px; display:inline-flex; align-items:center; justify-content:center; font-weight:700; font-size:1.125rem;"
                        >
                            {{ $config['label'] }}
                        </span>
                        <span style="font-size:0.75rem; color:#6b7280;">{{ $date }}</span>
                        <span style="font-size:0.7rem; color:#9ca3af;">{{ $gameTypeLabel }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
