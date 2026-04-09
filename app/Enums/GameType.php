<?php

namespace App\Enums;

enum GameType: string
{
    case Casual      = 'casual';
    case Competitive = 'competitive';
    case Training    = 'training';
    case Ranking     = 'ranking';

    public function affectsRanking(): bool
    {
        return $this === self::Ranking;
    }

    public function label(): string
    {
        return match ($this) {
            self::Casual      => 'Casual',
            self::Competitive => 'Competitivo',
            self::Training    => 'Treino',
            self::Ranking     => 'Ranking',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Casual      => 'gray',
            self::Competitive => 'blue',
            self::Training    => 'green',
            self::Ranking     => 'warning',
        };
    }
}
