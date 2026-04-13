<x-filament-panels::page>

    @php
        $levelLabels = [
            1 => '1 - Pro',
            2 => '2 - Avançado+',
            3 => '3 - Avançado',
            4 => '4 - Intermediário+',
            5 => '5 - Intermediário',
            6 => '6 - Iniciante+',
            7 => '7 - Iniciante',
        ];
        $sideLabels = [
            'left'  => 'Esquerda',
            'right' => 'Direita',
            'both'  => 'Ambos',
        ];
        $tabs = [
            'amigos'    => ['label' => 'Amigos',            'icon' => 'heroicon-o-user-group'],
            'recebidos' => ['label' => 'Pedidos Recebidos', 'icon' => 'heroicon-o-inbox'],
            'enviados'  => ['label' => 'Pedidos Enviados',  'icon' => 'heroicon-o-paper-airplane'],
            'buscar'    => ['label' => 'Buscar Jogadores',  'icon' => 'heroicon-o-magnifying-glass'],
        ];
    @endphp

    {{-- Abas --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 mb-6">
        @foreach($tabs as $key => $tab)
            <button
                wire:click="setTab('{{ $key }}')"
                @class([
                    'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                    'bg-primary-600 text-white shadow'                                     => $activeTab === $key,
                    'bg-gray-100 text-gray-600 hover:bg-gray-200'                          => $activeTab !== $key,
                ])
            >
                <x-filament::icon :icon="$tab['icon']" class="h-4 w-4" />
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ================================================================
         ABA: Amigos
    ================================================================ --}}
    @if($activeTab === 'amigos')
        <x-filament::section>
            <x-slot name="heading">Meus Amigos</x-slot>

            @if($amigos->isEmpty())
                <p class="text-sm text-gray-400">
                    Você ainda não tem amigos adicionados. Use a aba "Buscar Jogadores" para encontrar pessoas.
                </p>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($amigos as $amigo)
                        @php $isFav = $favoriteIds->contains($amigo->id); @endphp
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100">
                                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-primary-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $amigo->full_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $levelLabels[$amigo->level] ?? "Nível {$amigo->level}" }}
                                        &middot;
                                        {{ $sideLabels[$amigo->side] ?? $amigo->side }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    wire:click="toggleFavorito({{ $amigo->id }})"
                                    title="{{ $isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}"
                                    class="p-1.5 rounded-md transition-colors hover:bg-gray-100"
                                >
                                    @if($isFav)
                                        <x-filament::icon icon="heroicon-s-star" class="h-5 w-5 text-warning-400" />
                                    @else
                                        <x-filament::icon icon="heroicon-o-star" class="h-5 w-5 text-gray-400" />
                                    @endif
                                </button>
                                <x-filament::button
                                    wire:click="removerAmigo({{ $amigo->id }})"
                                    wire:confirm="Remover {{ $amigo->full_name }} dos seus amigos?"
                                    color="danger"
                                    size="sm"
                                    icon="heroicon-o-user-minus"
                                >
                                    Remover
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ================================================================
         ABA: Pedidos Recebidos
    ================================================================ --}}
    @if($activeTab === 'recebidos')
        <x-filament::section>
            <x-slot name="heading">Pedidos de Amizade Recebidos</x-slot>

            @if($recebidos->isEmpty())
                <p class="text-sm text-gray-400">
                    Nenhum pedido de amizade pendente.
                </p>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($recebidos as $pedido)
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-info-100">
                                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-info-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $pedido->player->full_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $levelLabels[$pedido->player->level] ?? "Nível {$pedido->player->level}" }}
                                        &middot; Enviado em {{ $pedido->created_at->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-filament::button
                                    wire:click="aceitarAmigo({{ $pedido->id }})"
                                    color="success"
                                    size="sm"
                                    icon="heroicon-o-check"
                                >
                                    Aceitar
                                </x-filament::button>
                                <x-filament::button
                                    wire:click="rejeitarAmigo({{ $pedido->id }})"
                                    color="danger"
                                    size="sm"
                                    icon="heroicon-o-x-mark"
                                >
                                    Rejeitar
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ================================================================
         ABA: Pedidos Enviados
    ================================================================ --}}
    @if($activeTab === 'enviados')
        <x-filament::section>
            <x-slot name="heading">Pedidos de Amizade Enviados</x-slot>

            @if($enviados->isEmpty())
                <p class="text-sm text-gray-400">
                    Nenhum pedido enviado aguardando resposta.
                </p>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($enviados as $pedido)
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-warning-100">
                                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-warning-600" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $pedido->friend->full_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $levelLabels[$pedido->friend->level] ?? "Nível {$pedido->friend->level}" }}
                                        &middot; Enviado em {{ $pedido->created_at->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <x-filament::button
                                wire:click="cancelarPedido({{ $pedido->id }})"
                                wire:confirm="Cancelar o pedido enviado para {{ $pedido->friend->full_name }}?"
                                color="warning"
                                size="sm"
                                icon="heroicon-o-x-circle"
                            >
                                Cancelar
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- ================================================================
         ABA: Buscar Jogadores
    ================================================================ --}}
    @if($activeTab === 'buscar')
        <x-filament::section>
            <x-slot name="heading">Buscar Jogadores</x-slot>

            <div class="mb-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.400ms="searchQuery"
                        placeholder="Digite o nome do jogador (mínimo 2 caracteres)..."
                    />
                </x-filament::input.wrapper>
            </div>

            @if(mb_strlen(trim($searchQuery)) < 2)
                <p class="text-sm text-gray-400">
                    Digite pelo menos 2 caracteres para buscar jogadores.
                </p>
            @elseif($buscarResult->isEmpty())
                <p class="text-sm text-gray-400">
                    Nenhum jogador encontrado para "{{ $searchQuery }}".
                </p>
            @else
                <div class="flex flex-col gap-3">
                    @foreach($buscarResult as $jogador)
                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                                    <x-filament::icon icon="heroicon-o-user" class="h-5 w-5 text-gray-500" />
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $jogador->full_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $levelLabels[$jogador->level] ?? "Nível {$jogador->level}" }}
                                        &middot;
                                        {{ $sideLabels[$jogador->side] ?? $jogador->side }}
                                    </p>
                                </div>
                            </div>
                            <x-filament::button
                                wire:click="enviarPedido({{ $jogador->id }})"
                                color="primary"
                                size="sm"
                                icon="heroicon-o-user-plus"
                            >
                                Adicionar Amigo
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

</x-filament-panels::page>
