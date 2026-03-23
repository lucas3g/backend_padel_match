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
            $allowed = array_filter(explode(',', env('HORIZON_ALLOWED_EMAILS', '')));
            return $request->user() && in_array($request->user()->email, $allowed);
        });
    }
}
