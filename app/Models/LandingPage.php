<?php

namespace App\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPage extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'translations',
        'event_id', 'slug', 'name', 'content', 'seo_title', 'seo_description',
        'seo_image', 'hero_image_query', 'hero_image_url', 'is_active',
    ];

    protected $casts = [
        'translations' => 'array','content' => 'array', 'is_active' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->localizedContent(), $key, $default);
    }

    /**
     * Editable content for the active language.
     *
     * The base columns hold Arabic; English lives under translations.en.content.
     * For a non-base language only its own values are returned — a key the admin
     * has not translated falls through to the lang file default in the view,
     * which is what keeps the English page from showing Arabic sentences.
     *
     * @return array<string,mixed>
     */
    public function localizedContent(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if ($locale === \App\Support\Locale::default()) {
            return (array) ($this->content ?? []);
        }

        $translated = (array) data_get($this->translations, $locale.'.content', []);

        return array_filter($translated, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** SEO value for this language only (no cross-language fallback). */
    public function seoTitle(?string $locale = null): ?string
    {
        return $this->localeValue('seo_title', $locale);
    }

    public function seoDescription(?string $locale = null): ?string
    {
        return $this->localeValue('seo_description', $locale);
    }

    protected function localeValue(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        if ($locale === \App\Support\Locale::default()) {
            return $this->{$field} ?: null;
        }

        return $this->rawTranslation($field, $locale) ?: null;
    }
}
