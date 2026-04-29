<?php

namespace App\Exceptions\Games;

use RuntimeException;

class PlayerLevelTooLowException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Seu nível é abaixo do mínimo exigido para esta partida');
    }
}
