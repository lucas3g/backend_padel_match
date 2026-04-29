<?php

namespace App\Exceptions\Games;

use RuntimeException;

class PlayerLevelTooHighException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Seu nível é acima do máximo permitido para esta partida');
    }
}
