<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\RankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateRankingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 60;

    public function __construct(private readonly int $gameId)
    {
        $this->onQueue('ranking');
    }

    public function handle(RankingService $rankingService): void
    {
        $game = Game::with([
            'players' => fn ($q) => $q->withPivot('team', 'elo_after'),
            'sets',
        ])->findOrFail($this->gameId);

        // Idempotência: se algum jogador já tem elo_after preenchido, o job já foi processado
        if ($game->players->filter(fn ($p) => $p->pivot->elo_after !== null)->isNotEmpty()) {
            Log::info('UpdateRankingJob: pulado (já processado)', ['game_id' => $this->gameId]);
            return;
        }

        $rankingService->processRankingGame($game);

        Log::info('UpdateRankingJob: ranking atualizado com sucesso', ['game_id' => $this->gameId]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('UpdateRankingJob: falhou', [
            'game_id' => $this->gameId,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);
    }
}
