<?php

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
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('director') || $request->is('director/*')) {
                return '/director/login';
            }

            if ($request->is('profesor') || $request->is('profesor/*')) {
                return '/profesor/login';
            }

            if ($request->is('super-admin') || $request->is('super-admin/*')) {
                return '/super-admin';
            }

            if ($request->is('general-attendance') || $request->is('general-attendance/*')) {
                return '/';
            }

            return '/login';
        });

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
