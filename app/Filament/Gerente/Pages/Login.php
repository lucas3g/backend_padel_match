<?php

namespace App\Filament\Gerente\Pages;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends \Filament\Pages\Auth\Login
{
    public function authenticate(): LoginResponse
    {
        session()->forget('url.intended');

        return parent::authenticate();
    }

    protected function getRedirectUrl(): string
    {
        return Filament::getPanel('gerente')->getUrl();
    }
}
