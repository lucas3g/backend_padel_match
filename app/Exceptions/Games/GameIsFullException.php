<?php

namespace App\Exceptions\Games;

use RuntimeException;

class GameIsFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A partida já atingiu o número máximo de jogadores');
    }
}
