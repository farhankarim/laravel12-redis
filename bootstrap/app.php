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
        // Trust proxy headers from the first hop only.
        // Set TRUSTED_PROXIES in your environment to a comma-separated list of
        // upstream proxy IP addresses (e.g. "10.0.0.1,10.0.0.2") or "*" when
        // your infrastructure guarantees only real proxies send these headers.
        $middleware->trustProxies(
            at: explode(',', (string) env('TRUSTED_PROXIES', '')),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
