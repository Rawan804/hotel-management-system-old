<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\CustomerOnly;
use App\Http\Middleware\StaffOnly;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->append(SetLocale::class);

        $middleware->alias([
            'customer' => CustomerOnly::class,
            'staff' => StaffOnly::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })
    ->create();