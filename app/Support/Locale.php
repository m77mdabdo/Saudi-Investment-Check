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

    /**
     * Canonical metadata for every language the product ships with.
     *
     * This lives in code — not only in config — on purpose. `config/creativemark.php`
     * may extend or override any of it, but the locale system must never depend on
     * a config key being present: a stale `bootstrap/cache/config.php` (an old release
     * cached before these keys existed) used to make meta() return null, which broke
     * every page with a TypeError. Code is the floor, config is the override.
     */
    public const LOCALES = [
        'ar' => [
            'code' => 'ar',
            'name' => 'Arabic',
            'native' => 'العربية',
            'dir' => 'rtl',
            'flag' => '🇸🇦',
            'html' => 'ar',
            'iso' => 'ar_SA',
            'hreflang' => 'ar',
        ],
        'en' => [
            'code' => 'en',
            'name' => 'English',
            'native' => 'English',
            'dir' => 'ltr',
            'flag' => '🇬🇧',
            'html' => 'en',
            'iso' => 'en_GB',
            'hreflang' => 'en',
        ],
    ];

    /** Used when nothing else can be resolved. Always a key of self::LOCALES. */
    public const FALLBACK = 'ar';

    /** Keys every metadata array is guaranteed to expose. */
    public const META_KEYS = ['code', 'name', 'native', 'dir', 'flag', 'html', 'iso', 'hreflang'];

    /** @return array<int,string> */
    public static function supported(): array
    {
        $configured = config('creativemark.locales');

        $codes = is_array($configured)
            ? array_values(array_filter(array_keys($configured), fn ($code) => is_string($code) && $code !== ''))
            : [];

        return $codes !== [] ? $codes : array_keys(self::LOCALES);
    }

    /** The language the un-prefixed URLs and the base database columns use. */
    public static function default(): string
    {
        $configured = config('creativemark.base_locale');
        $supported = static::supported();

        if (is_string($configured) && in_array($configured, $supported, true)) {
            return $configured;
        }

        return in_array(self::FALLBACK, $supported, true) ? self::FALLBACK : $supported[0];
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && in_array($locale, static::supported(), true);
    }

    /**
     * Metadata for a language. Never returns null and never returns a partial
     * array: an unknown, malformed or missing locale falls back to the default
     * language, and any key missing from config is filled from self::LOCALES.
     *
     * @return array<string,string>
     */
    public static function meta(?string $locale = null): array
    {
        $locale = static::normalise($locale);
        $base = self::LOCALES[$locale] ?? static::genericMeta($locale);

        $configured = config('creativemark.locales.'.$locale);

        if (is_array($configured)) {
            $base = array_merge($base, array_filter(
                $configured,
                fn ($value) => is_scalar($value) && $value !== '',
            ));
        }

        // Guarantee the full contract even if config overrode it with rubbish.
        foreach (static::genericMeta($locale) as $key => $value) {
            if (! isset($base[$key]) || ! is_string($base[$key]) || $base[$key] === '') {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /** Metadata for every supported language, keyed by code. */
    public static function all(): array
    {
        $locales = [];

        foreach (static::supported() as $code) {
            $locales[$code] = static::meta($code);
        }

        return $locales;
    }

    /**
     * Turn anything into a usable locale code: `en-GB`/`en_US` become `en`, and
     * an unsupported or empty value becomes the default language.
     */
    public static function normalise(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if (! is_string($locale) || $locale === '') {
            return static::default();
        }

        if (static::isSupported($locale)) {
            return $locale;
        }

        $short = strtolower(str_replace('_', '-', $locale));
        $short = explode('-', $short)[0];

        return static::isSupported($short) ? $short : static::default();
    }

    /** Safe defaults for a locale with no canonical entry (e.g. a future language). */
    protected static function genericMeta(string $locale): array
    {
        return [
            'code' => $locale,
            'name' => strtoupper($locale),
            'native' => strtoupper($locale),
            'dir' => in_array($locale, ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr',
            'flag' => '🌐',
            'html' => $locale,
            'iso' => $locale,
            'hreflang' => $locale,
        ];
    }

    public static function direction(?string $locale = null): string
    {
        return static::meta($locale)['dir'];
    }

    public static function isRtl(?string $locale = null): bool
    {
        return static::direction($locale) === 'rtl';
    }

    /** The other locale — used by the language switcher. */
    public static function alternate(?string $locale = null): string
    {
        $locale = static::normalise($locale);

        return collect(static::supported())->first(fn ($code) => $code !== $locale) ?? static::default();
    }

    /**
     * Resolve the locale for a request: URL segment → session → cookie →
     * Accept-Language → default.
     */
    public static function resolve(Request $request): string
    {
        return static::normalise(static::detect($request));
    }

    /** @see resolve() — this does the detection, resolve() guarantees the value. */
    protected static function detect(Request $request): ?string
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
        $locale = static::normalise($locale);
        $base = $name;

        foreach (static::supported() as $code) {
            if ($code !== static::default() && Str::startsWith($name, $code.'.')) {
                $base = Str::after($name, $code.'.');

                break;
            }
        }

        return $locale === static::default() ? $base : $locale.'.'.$base;
    }

    /** Same page, other language — falls back to that language's home page. */
    public static function alternateUrl(?string $locale = null): string
    {
        $locale = static::normalise($locale ?? static::alternate());
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
