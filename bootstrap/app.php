<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCanManagePlatform;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Language is resolved for every web request (public and admin).
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'admin.active' => EnsureAdmin::class,
            'admin.manage' => EnsureCanManagePlatform::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // Only trust proxies the deployment actually sits behind — trusting all
        // would let a client spoof its IP and bypass the per-IP rate limits.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
