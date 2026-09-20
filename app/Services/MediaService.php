<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\MediaAsset;
use App\Models\ResultRule;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves imagery for a named slot (hero, result_ready, auth, ...).
 *
 * Public requests read cached database rows only — a remote API is never
 * called while a visitor waits. Slots are filled by `php artisan media:sync`
 * or from Admin → Media, and always degrade to a bundled local image.
 */
class MediaService
{
    public const SLOTS = [
        'hero' => 'Landing hero',
        'result_ready' => 'Result — Ready',
        'result_needs_prep' => 'Result — Needs prep',
        'result_early' => 'Result — Early stage',
        'quiz' => 'Quiz backdrop',
        'auth' => 'Admin login',
        'event' => 'Event section',
        'empty' => 'Empty states',
    ];

    public function __construct(protected PexelsClient $pexels) {}

    /** @return array{url:string,credit:?string,credit_url:?string,color:string,remote:bool} */
    public function slot(string $slot): array
    {
        $asset = Cache::remember('media.slot.'.$slot, 3600, fn () => MediaAsset::query()
            ->where('slot', $slot)->where('is_active', true)->latest('id')->first());

        if ($asset) {
            return [
                'url' => $asset->url,
                'credit' => $asset->photographer,
                'credit_url' => $asset->photographer_url,
                'color' => $asset->avg_color ?: '#0b1220',
                'remote' => true,
            ];
        }

        return [
            'url' => asset($this->fallbackPath($slot)),
            'credit' => null,
            'credit_url' => null,
            'color' => '#0b1220',
            'remote' => false,
        ];
    }

    public function url(string $slot): string
    {
        return $this->slot($slot)['url'];
    }

    public function fallbackPath(string $slot): string
    {
        $map = config('creativemark.media.fallbacks', []);
        $key = match (true) {
            str_starts_with($slot, 'result') => 'result',
            $slot === 'auth' => 'auth',
            $slot === 'event' => 'event',
            $slot === 'empty' => 'empty',
            $slot === 'portal' => 'portal',
            default => 'hero',
        };

        return $map[$key] ?? 'images/fallback/hero.svg';
    }

    public function defaultQuery(string $slot): string
    {
        // Result slots follow whatever the admin typed on the matching result rule.
        if (str_starts_with($slot, 'result_')) {
            $ruleKey = $slot === 'result_early' ? 'early' : substr($slot, 7);
            $query = ResultRule::query()->where('key', $ruleKey)->value('image_query');

            if (filled($query)) {
                return $query;
            }
        }

        if ($slot === 'hero') {
            $query = LandingPage::query()->where('slug', 'default')->value('hero_image_query');

            if (filled($query)) {
                return $query;
            }
        }

        $queries = config('creativemark.media.queries', []);
        $key = match (true) {
            str_starts_with($slot, 'result') => 'result',
            $slot === 'auth' => 'auth',
            $slot === 'event' => 'event',
            $slot === 'empty' => 'empty',
            default => 'hero',
        };

        return $queries[$key] ?? 'riyadh skyline';
    }

    /** Fetch a fresh photo for a slot and store its metadata. Admin/CLI only. */
    public function refresh(string $slot, ?string $query = null): ?MediaAsset
    {
        $query = $query ?: $this->defaultQuery($slot);
        $photos = $this->pexels->search($query, 8);

        if ($photos === []) {
            return null;
        }

        $photo = $photos[array_rand($photos)];

        MediaAsset::query()->where('slot', $slot)->update(['is_active' => false]);

        $asset = MediaAsset::create([
            'slot' => $slot,
            'provider' => 'pexels',
            'external_id' => $photo['external_id'] ?? null,
            'query' => $query,
            'url' => $photo['url'],
            'thumb_url' => $photo['thumb_url'] ?? null,
            'photographer' => $photo['photographer'] ?? null,
            'photographer_url' => $photo['photographer_url'] ?? null,
            'avg_color' => $photo['avg_color'] ?? null,
            'width' => $photo['width'] ?? null,
            'height' => $photo['height'] ?? null,
            'is_active' => true,
        ]);

        $this->forget($slot);

        return $asset;
    }

    public function pin(string $slot, array $photo, ?string $query = null): MediaAsset
    {
        MediaAsset::query()->where('slot', $slot)->update(['is_active' => false]);

        $asset = MediaAsset::create([
            'slot' => $slot,
            'provider' => 'pexels',
            'external_id' => $photo['external_id'] ?? null,
            'query' => $query,
            'url' => $photo['url'],
            'thumb_url' => $photo['thumb_url'] ?? null,
            'photographer' => $photo['photographer'] ?? null,
            'photographer_url' => $photo['photographer_url'] ?? null,
            'avg_color' => $photo['avg_color'] ?? null,
            'width' => $photo['width'] ?? null,
            'height' => $photo['height'] ?? null,
            'is_active' => true,
        ]);

        $this->forget($slot);

        return $asset;
    }

    public function clear(string $slot): void
    {
        MediaAsset::query()->where('slot', $slot)->update(['is_active' => false]);
        $this->forget($slot);
    }

    public function forget(string $slot): void
    {
        Cache::forget('media.slot.'.$slot);
    }
}
