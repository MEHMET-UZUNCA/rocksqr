<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('screens:clear-if-due')->everyMinute()->withoutOverlapping();
        $schedule->command('sync:mssql-prices')->everyMinute()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SubdomainRedirect::class,
        ]);

        $middleware->auth(append: [
            \App\Http\Middleware\EnsureUserActive::class,
        ]);

        $middleware->alias([
            'screen-pin' => \App\Http\Middleware\EnsureScreenPin::class,
            'admin.role' => \App\Http\Middleware\EnsureAdminRole::class,
        ]);
        // Bar ve Kitchen display ekranları — uzun süre açık kalır, CSRF token expire olur
        $middleware->validateCsrfTokens(except: [
            'bar/orders/*/cancel',
            'bar/orders/*/status',
            'bar/symphony/delivered',
            'bar/waiter-calls/*/attend',
            'kitchen/orders/*/status',
            'kitchen/orders/*/ack-cancel',
            'kitchen-pos/complete',
            'kitchen-pos/uncomplete',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();