<?php

use App\Support\Locale;

if (! function_exists('lroute')) {
    /**
     * URL for a public route in the current (or given) language.
     * `lroute('quiz')` → /quiz in Arabic, /en/quiz in English.
     */
    function lroute(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true): string
    {
        return route(Locale::routeName($name, $locale), $parameters, $absolute);
    }
}

if (! function_exists('locale_dir')) {
    function locale_dir(?string $locale = null): string
    {
        return Locale::direction($locale);
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(?string $locale = null): bool
    {
        return Locale::isRtl($locale);
    }
}
