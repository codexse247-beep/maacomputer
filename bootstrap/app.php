<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The site is only reachable through the local Cloudflare tunnel, so trust its forwarded headers.
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [\App\Http\Middleware\NormalizeDigits::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('portal*') ? route('portal.dashboard') : route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
