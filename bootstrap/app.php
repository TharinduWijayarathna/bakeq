<?php

use App\Http\Middleware\EnsureStaffCan;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(5)->by($request->ip());
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'staff.can' => EnsureStaffCan::class,
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('login'));

        $middleware->redirectUsersTo(function (): string {
            return auth()->user()?->isStaff()
                ? route('admin.dashboard')
                : route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
