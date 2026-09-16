<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Switches the interface language and sends the visitor back to the same page
 * in the other language. Only relative, same-host targets are followed.
 */
class LanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locale::isSupported($locale), 404);

        $request->session()->put(Locale::SESSION_KEY, $locale);
        app()->setLocale($locale);

        $target = (string) $request->query('redirect', '');
        $safe = $this->safeTarget($target, $locale);

        return redirect()->to($safe)->withCookie(
            Cookie::make(Locale::COOKIE, $locale, 60 * 24 * 365, '/', null, $request->secure(), true, false, 'Lax')
        );
    }

    /** Never redirect off-site: only same-origin paths are accepted. */
    protected function safeTarget(string $target, string $locale): string
    {
        $home = $locale === Locale::default() ? url('/') : url('/'.$locale);

        if ($target === '' || str_contains($target, "\n") || str_starts_with($target, '//')) {
            return $home;
        }

        if (str_starts_with($target, 'http')) {
            return str_starts_with($target, url('/')) ? $target : $home;
        }

        return url('/'.ltrim($target, '/'));
    }
}
