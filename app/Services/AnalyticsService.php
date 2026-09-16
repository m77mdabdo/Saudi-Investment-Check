<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    public function __construct(protected VisitorContext $context) {}

    /** Records a funnel event. Never throws into the user experience. */
    public function record(string $name, Request $request, ?Lead $lead = null, array $payload = []): ?AnalyticsEvent
    {
        if (! in_array($name, AnalyticsEvent::NAMES, true)) {
            return null;
        }

        try {
            $ctx = $this->context->get($request);

            return AnalyticsEvent::create([
                'name' => $name,
                'event_id' => $ctx['event_id'] ?? null,
                'lead_id' => $lead?->id,
                'qr_source_id' => $ctx['qr_source_id'] ?? null,
                'session_hash' => $ctx['session_hash'] ?? null,
                'source' => $ctx['source'] ?? null,
                'utm_source' => $ctx['utm_source'] ?? null,
                'utm_medium' => $ctx['utm_medium'] ?? null,
                'utm_campaign' => $ctx['utm_campaign'] ?? null,
                'utm_content' => $ctx['utm_content'] ?? null,
                'device' => $ctx['device'] ?? null,
                'browser' => $ctx['browser'] ?? null,
                'payload' => $payload ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('analytics.record_failed', ['name' => $name, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** Records an event at most once per session (e.g. page views, quiz start). */
    public function recordOnce(string $name, Request $request, array $payload = []): ?AnalyticsEvent
    {
        $key = 'smrc.analytics.'.$name;

        if ($request->session()->get($key)) {
            return null;
        }

        $request->session()->put($key, true);

        return $this->record($name, $request, null, $payload);
    }
}
