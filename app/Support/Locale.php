<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Locale rules for the public site.
 *
 * Arabic is the default and keeps the original, un-prefixed URLs so every link
 * that already exists in the wild (QR codes, printed material, indexed pages)
 * keeps working. English lives under /en/… so both languages have their own
 * canonical URL, which is what hreflang needs.
 */
class Locale
{
    public const SESSION_KEY = 'smrc.locale';
    public const COOKIE = 'smrc_locale';

    /** @return array<int,string> */
    public static function supported(): array
    {
        return array_keys(config('creativemark.locales', ['ar' => [], 'en' => []]));
    }

    public static function default(): string
    {
        return config('creativemark.base_locale', 'ar');
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && in_array($locale, static::supported(), true);
    }

    public static function meta(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return config('creativemark.locales.'.$locale, config('creativemark.locales.'.static::default()));
    }

    public static function direction(?string $locale = null): string
    {
        return static::meta($locale)['dir'] ?? 'rtl';
    }

    public static function isRtl(?string $locale = null): bool
    {
        return static::direction($locale) === 'rtl';
    }

    /** The other locale — used by the language switcher. */
    public static function alternate(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return collect(static::supported())->first(fn ($l) => $l !== $locale) ?? static::default();
    }

    /**
     * Resolve the locale for a request: URL segment → session → cookie →
     * Accept-Language → default.
     */
    public static function resolve(Request $request): string
    {
        /*
         | The URL is authoritative. A localized route decides the language by
         | itself so that /quiz always renders Arabic and /en/quiz always
         | renders English, whatever the visitor's session happens to hold.
         */
        if ($name = $request->route()?->getName()) {
            foreach (static::supported() as $locale) {
                if ($locale !== static::default() && str_starts_with($name, $locale.'.')) {
                    return $locale;
                }
            }

            // An un-prefixed name that also exists as a prefixed route is the
            // default-language variant of a localized page.
            if (app('router')->has(static::alternate(static::default()).'.'.$name)) {
                return static::default();
            }
        }

        $segment = $request->segment(1);

        if (static::isSupported($segment)) {
            return $segment;
        }

        /*
         | Form posts carry the language of the page they were rendered on in a
         | hidden field — more reliable than a Referer header, which browsers
         | and privacy settings may strip. The value is whitelisted below.
         */
        if ($request->isMethod('POST') && static::isSupported($request->input('locale'))) {
            return (string) $request->input('locale');
        }

        // Otherwise follow the page the request came from.
        if ($referer = $request->headers->get('referer')) {
            $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            $first = Str::before($path, '/') ?: $path;

            if (static::isSupported($first) && $request->isMethod('POST')) {
                return $first;
            }
        }

        $stored = [
            $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null,
            $request->cookie(self::COOKIE),
        ];

        foreach ($stored as $candidate) {
            if (static::isSupported($candidate)) {
                return $candidate;
            }
        }

        // Browser auto-detection is opt-in: the brand's primary language is
        // Arabic, and a visitor whose phone is set to English should still land
        // on the Arabic page unless they pick English themselves.
        if (! config('creativemark.auto_detect_locale', false) || ! $request->headers->has('Accept-Language')) {
            return static::default();
        }

        $preferred = $request->getPreferredLanguage(static::supported());

        return static::isSupported($preferred) ? $preferred : static::default();
    }

    /** Route name for a locale: `landing` in Arabic, `en.landing` in English. */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $base = Str::startsWith($name, 'en.') ? Str::after($name, 'en.') : $name;

        return $locale === static::default() ? $base : $locale.'.'.$base;
    }

    /** Same page, other language — falls back to that language's home page. */
    public static function alternateUrl(?string $locale = null): string
    {
        $locale ??= static::alternate();
        $route = request()->route();

        if (! $route || ! $route->getName()) {
            return $locale === static::default() ? url('/') : url('/'.$locale);
        }

        $name = static::routeName($route->getName(), $locale);

        if (! app('router')->has($name)) {
            return $locale === static::default() ? url('/') : url('/'.$locale);
        }

        return route($name, $route->parameters() + request()->query());
    }
}
