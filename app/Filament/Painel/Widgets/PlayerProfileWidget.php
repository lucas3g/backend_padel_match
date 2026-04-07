<?php

namespace App\Filament\Painel\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlayerProfileWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $player = auth()->user()?->player;

        if (! $player) {
            return [];
        }

        $player->load('municipio');

        $levelLabels = [
            1  => 'Iniciante (1)',
            2  => 'Iniciante (2)',
            3  => 'Básico (3)',
            4  => 'Básico (4)',
            5  => 'Intermediário (5)',
            6  => 'Intermediário (6)',
            7  => 'Avançado (7)',
            8  => 'Avançado (8)',
            9  => 'Semi-Pro (9)',
            10 => 'Profissional (10)',
        ];

        $sideLabels = [
            'left'  => 'Esquerda',
            'right' => 'Direita',
            'both'  => 'Ambos',
        ];

        $disponibilidadeConfig = [
            'disponivel' => ['label' => 'Disponível',  'color' => 'success'],
            'machucado'  => ['label' => 'Machucado',   'color' => 'danger'],
            'viajando'   => ['label' => 'Viajando',    'color' => 'warning'],
            'licenca'    => ['label' => 'De Licença',  'color' => 'gray'],
        ];

        $disp     = $player->disponibilidade ?? 'disponivel';
        $dispInfo = $disponibilidadeConfig[$disp] ?? $disponibilidadeConfig['disponivel'];

        $localidade = collect([$player->municipio?->descricao, $player->uf])
            ->filter()
            ->implode(' / ');

        $dispDescricao = $player->motivo_indisponibilidade
            ?? ($player->disponivel_ate ? 'Retorno: ' . $player->disponivel_ate->format('d/m/Y') : null)
            ?? '';

        return [
            Stat::make('Jogador', $player->full_name)
                ->description($levelLabels[$player->level] ?? "Nível {$player->level}")
                ->icon('heroicon-o-user')
                ->color('primary'),

            Stat::make('Lado', $sideLabels[$player->side] ?? $player->side)
                ->description($localidade ?: 'Localização não informada')
                ->icon('heroicon-o-map-pin')
                ->color('info'),

            Stat::make('Ranking', $player->ranking_position ? "#{$player->ranking_position}" : 'Não ranqueado')
                ->description("{$player->ranking_points} pontos")
                ->icon('heroicon-o-trophy')
                ->color('warning'),

            Stat::make('Disponibilidade', $dispInfo['label'])
                ->description($dispDescricao)
                ->icon('heroicon-o-signal')
                ->color($dispInfo['color']),
        ];
    }
}
