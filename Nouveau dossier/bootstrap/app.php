<?php

use App\Console\Commands\ExpireOldListings;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Sweeps expired listings off the public market once a day.
        // ⚠️ Requires something to actually trigger `php artisan schedule:run` every minute —
        // see the "Auto-expiry cron" note in README.md for how to set this up on Railway.
        $schedule->command(ExpireOldListings::class)->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
