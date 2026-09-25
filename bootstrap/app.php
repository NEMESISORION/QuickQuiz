<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureRoleSelected;
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
        $middleware->append(AddSecurityHeaders::class);
        $middleware->authenticateSessions();
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT,
        );

        $middleware->trustHosts(
            at: fn (): array => app()->isProduction()
                ? ['^'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST), '/').'$']
                : ['.*'],
            subdomains: false,
        );

        $middleware->alias([
            'role.selected' => EnsureRoleSelected::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
