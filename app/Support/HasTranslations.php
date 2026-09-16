<?php

namespace App\Support;

/**
 * Lightweight per-model translations stored in a `translations` JSON column:
 *
 *   ['en' => ['title' => 'Company stage', 'subtitle' => '...']]
 *
 * The base columns always hold the Arabic (default locale) copy, so nothing
 * breaks when a translation is missing — it simply falls back.
 */
trait HasTranslations
{
    /** Translated value for the current (or given) locale, falling back to the base column. */
    public function t(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $fallback = $this->{$field};

        if ($locale === config('creativemark.base_locale', 'ar')) {
            return $fallback;
        }

        $value = data_get($this->translations, $locale.'.'.$field);

        return filled($value) ? $value : $fallback;
    }

    /** Translated array value (e.g. result bullets). */
    public function tArray(string $field, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $fallback = (array) ($this->{$field} ?? []);

        if ($locale === config('creativemark.base_locale', 'ar')) {
            return $fallback;
        }

        $value = data_get($this->translations, $locale.'.'.$field);

        return is_array($value) && $value !== [] ? $value : $fallback;
    }

    /** Raw stored translation, for admin forms (no fallback). */
    public function rawTranslation(string $field, string $locale): ?string
    {
        $value = data_get($this->translations, $locale.'.'.$field);

        return is_array($value) ? implode("\n", $value) : $value;
    }

    /** @param array<string,mixed> $values */
    public function setTranslations(string $locale, array $values): void
    {
        $translations = $this->translations ?? [];
        $existing = $translations[$locale] ?? [];

        foreach ($values as $field => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($existing[$field]);

                continue;
            }

            $existing[$field] = $value;
        }

        if ($existing === []) {
            unset($translations[$locale]);
        } else {
            $translations[$locale] = $existing;
        }

        $this->translations = $translations ?: null;
    }
}
