<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Railway terminates SSL at its proxy and forwards plain HTTP to the container,
        // so without this, asset()/Storage::url()/url() all generate http:// links
        // even though the site is served over https:// (causes Mixed Content errors
        // and broken car photos on the frontend).
        if (env('APP_ENV') === 'production' || env('FORCE_HTTPS', true)) {
            URL::forceScheme('https');
        }

        // This is an API-only project (no Blade "password.reset" web route exists),
        // so we point the reset-password email link straight at our frontend page instead.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5500');

            return "{$frontendUrl}/pages/reset-password.html?token={$token}&email=".urlencode($user->email);
        });
    }
}
