<?php

namespace App\Exceptions\Games;

use RuntimeException;

class GameNotOpenException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A partida não está mais aberta');
    }
}
