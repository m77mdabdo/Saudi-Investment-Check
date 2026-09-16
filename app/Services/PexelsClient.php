<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin, cached wrapper around the Pexels API.
 * The API key lives in the environment only and never reaches the browser.
 */
class PexelsClient
{
    public function configured(): bool
    {
        return filled(config('services.pexels.key'));
    }

    /**
     * Search photos. Results (metadata only) are cached; failures return [].
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $perPage = 12, string $orientation = 'landscape'): array
    {
        if (! $this->configured() || trim($query) === '') {
            return [];
        }

        $cacheKey = 'pexels:'.md5($query.'|'.$perPage.'|'.$orientation);

        return Cache::remember($cacheKey, (int) config('services.pexels.cache_ttl', 86400), function () use ($query, $perPage, $orientation) {
            try {
                $response = Http::withHeaders(['Authorization' => (string) config('services.pexels.key')])
                    ->timeout((float) config('services.pexels.timeout', 4))
                    ->retry(1, 200)
                    ->get(rtrim((string) config('services.pexels.endpoint'), '/').'/search', [
                        'query' => $query,
                        'per_page' => max(1, min($perPage, 30)),
                        'orientation' => $orientation,
                    ]);

                if (! $response->successful()) {
                    Log::warning('pexels.request_failed', ['status' => $response->status()]);

                    return [];
                }

                return collect($response->json('photos') ?? [])
                    ->map(fn (array $photo) => [
                        'external_id' => (string) ($photo['id'] ?? ''),
                        'url' => $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? null,
                        'thumb_url' => $photo['src']['medium'] ?? $photo['src']['small'] ?? null,
                        'photographer' => $photo['photographer'] ?? null,
                        'photographer_url' => $photo['photographer_url'] ?? null,
                        'avg_color' => $photo['avg_color'] ?? null,
                        'width' => $photo['width'] ?? null,
                        'height' => $photo['height'] ?? null,
                        'alt' => $photo['alt'] ?? null,
                    ])
                    ->filter(fn (array $p) => filled($p['url']))
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('pexels.exception', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }
}
