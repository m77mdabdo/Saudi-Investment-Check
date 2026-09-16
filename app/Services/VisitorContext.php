<?php

namespace App\Services;

use App\Models\Event;
use App\Models\QrSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Resolves and remembers campaign attribution (source / QR / UTM / device)
 * for the whole public journey. Nothing sensitive is stored client-side.
 */
class VisitorContext
{
    public const SESSION_KEY = 'smrc.attribution';

    public function capture(Request $request): array
    {
        $existing = $request->session()->get(self::SESSION_KEY, []);

        $incoming = array_filter([
            'source' => $this->clean($request->query('source') ?? $request->query('utm_source')),
            'utm_source' => $this->clean($request->query('utm_source')),
            'utm_medium' => $this->clean($request->query('utm_medium')),
            'utm_campaign' => $this->clean($request->query('utm_campaign')),
            'utm_content' => $this->clean($request->query('utm_content')),
        ], fn ($v) => $v !== null && $v !== '');

        $data = array_merge($existing, $incoming);

        if (! empty($data['source']) && empty($data['qr_source_id'])) {
            $qr = QrSource::query()->where('slug', $data['source'])->where('is_active', true)->first();

            if ($qr) {
                $data['qr_source_id'] = $qr->id;
                $data['event_id'] = $qr->event_id ?: ($data['event_id'] ?? null);
                QrSource::query()->whereKey($qr->id)->increment('scans');
            }
        }

        $data['event_id'] ??= Event::current()?->id;
        $data += $this->agent($request);
        $data['session_hash'] = $existing['session_hash'] ?? hash('sha256', $request->session()->getId());

        $request->session()->put(self::SESSION_KEY, $data);

        return $data;
    }

    public function get(Request $request): array
    {
        $data = $request->session()->get(self::SESSION_KEY);

        return is_array($data) && $data !== [] ? $data : $this->capture($request);
    }

    public function agent(Request $request): array
    {
        $ua = (string) $request->userAgent();
        $mobile = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone/i', $ua);
        $tablet = (bool) preg_match('/iPad|Tablet/i', $ua);

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            str_contains($ua, 'Firefox') => 'Firefox',
            default => 'Other',
        };

        $platform = match (true) {
            str_contains($ua, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad|iPod/', $ua) => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Other',
        };

        return [
            'device' => $tablet ? 'tablet' : ($mobile ? 'mobile' : 'desktop'),
            'browser' => $browser,
            'platform' => $platform,
            'locale' => substr((string) $request->getPreferredLanguage(), 0, 10),
        ];
    }

    public function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }

    protected function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return Str::of($value)->trim()->limit(60, '')->replaceMatches('/[^\w\-\.\s]/u', '')->value() ?: null;
    }
}
