<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Horizon::auth(function ($request) {
            $allowed = array_filter(explode(',', config('services.horizon.allowed_emails', '')));
            return $request->user() && in_array($request->user()->email, $allowed);
        });
    }
}
