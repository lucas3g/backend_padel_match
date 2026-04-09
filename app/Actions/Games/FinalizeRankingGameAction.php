<?php

namespace App\Actions\Games;

use App\Models\Game;
use Illuminate\Validation\ValidationException;

class FinalizeRankingGameAction
{
    /**
     * Valida que uma partida do tipo ranking possui todos os dados obrigatórios
     * antes de ser finalizada: vencedor e pelo menos um set registrado.
     *
     * @throws ValidationException
     */
    public function validate(Game $game, array $data): void
    {
        $errors = [];

        if (empty($data['winner_team'])) {
            $errors['winner_team'] = 'Partidas de ranking exigem um time vencedor (winner_team: 1 ou 2).';
        }

        if (empty($data['sets'])) {
            $errors['sets'] = 'Partidas de ranking exigem ao menos um set com placar registrado.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
