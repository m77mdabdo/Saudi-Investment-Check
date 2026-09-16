<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locale::resolve($request);

        app()->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put(Locale::SESSION_KEY, $locale);
        }

        // Carbon follows the UI language so relative dates read naturally.
        \Carbon\Carbon::setLocale($locale);
        \Carbon\CarbonImmutable::setLocale($locale);

        // Queued (not attached) so it also works for downloads and other
        // responses that are not Illuminate responses.
        if ($request->cookie(Locale::COOKIE) !== $locale) {
            Cookie::queue(
                Cookie::make(Locale::COOKIE, $locale, 60 * 24 * 365, '/', null, $request->secure(), true, false, 'Lax')
            );
        }

        return $next($request);
    }
}
