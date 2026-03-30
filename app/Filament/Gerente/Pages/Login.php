<?php

namespace App\Filament\Gerente\Pages;

use Filament\Facades\Filament;

class Login extends \Filament\Pages\Auth\Login
{
    protected function getRedirectUrl(): string
    {
        return Filament::getPanel('gerente')->getUrl();
    }
}
