<?php

namespace App\Exceptions\Games;

use RuntimeException;

class TeamIsFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O time já está completo');
    }
}
