<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    /** @var array<string,mixed>|null */
    protected ?array $cache = null;

    /** @return array<string,mixed> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = Cache::remember('settings.all', 3600, function () {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Setting::query()->get()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $this->castValue($s->value, $s->type)])
                ->all();
        });

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type, 'group' => $group],
        );

        $this->flush();
    }

    public function flush(): void
    {
        $this->cache = null;
        Cache::forget('settings.all');
    }

    /**
     * Value for the active language. English copies live next to the Arabic
     * ones as `<key>_en`, so translating a setting never needs a migration.
     */
    public function localized(string $key, mixed $default = null): mixed
    {
        $locale = app()->getLocale();

        if ($locale !== config('creativemark.base_locale', 'ar')) {
            $translated = $this->get($key.'_'.$locale);

            if (filled($translated)) {
                return $translated;
            }
        }

        return $this->get($key, $default);
    }

    /** Public CTA links: settings override config, config overrides empty. */
    public function cta(string $key): string
    {
        return (string) $this->get('cta_'.$key, config('creativemark.cta.'.$key, ''));
    }

    /** @return array<string,string> */
    public function ctaLinks(): array
    {
        return [
            'whatsapp_url' => $this->cta('whatsapp_url'),
            'booking_url' => $this->cta('booking_url'),
            'checklist_url' => $this->cta('checklist_url'),
            'phone' => $this->cta('phone'),
            'email' => $this->cta('email'),
            'website' => $this->cta('website'),
        ];
    }

    protected function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'int' => (int) $value,
            'json' => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }
}
