<?php

namespace App\Filament\Painel\Widgets;

use App\Models\ClubPlayerRanking;
use Filament\Widgets\Widget;

class ClubRankingWidget extends Widget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.painel.widgets.club-ranking-widget';

    protected function getViewData(): array
    {
        $player = auth()->user()?->player;

        if (! $player) {
            return ['clubes' => []];
        }

        $clubesFavoritos = $player->favoriteClubs()->get();

        if ($clubesFavoritos->isEmpty()) {
            return ['clubes' => []];
        }

        $rankings = ClubPlayerRanking::where('player_id', $player->id)
            ->whereIn('club_id', $clubesFavoritos->pluck('id'))
            ->get()
            ->keyBy('club_id');

        $clubes = $clubesFavoritos->map(function ($clube) use ($rankings) {
            $ranking = $rankings->get($clube->id);

            return [
                'club_name'               => $clube->name,
                'club_position'           => $ranking?->club_position,
                'club_elo'                => $ranking?->club_elo,
                'ranking_matches_at_club' => $ranking?->ranking_matches_at_club ?? 0,
                'ranking_wins_at_club'    => $ranking?->ranking_wins_at_club ?? 0,
                'ranking_losses_at_club'  => $ranking?->ranking_losses_at_club ?? 0,
                'win_rate_at_club'        => $ranking?->win_rate_at_club,
            ];
        })->values()->toArray();

        return ['clubes' => $clubes];
    }
}
