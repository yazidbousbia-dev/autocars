<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // This is an API-only project (no Blade "password.reset" web route exists),
        // so we point the reset-password email link straight at our frontend page instead.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5500');

            return "{$frontendUrl}/pages/reset-password.html?token={$token}&email=".urlencode($user->email);
        });
    }
}
