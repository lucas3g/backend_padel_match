<?php

namespace App\Filament\Painel\Pages;

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
        return Filament::getPanel('painel')->getUrl();
    }
}
