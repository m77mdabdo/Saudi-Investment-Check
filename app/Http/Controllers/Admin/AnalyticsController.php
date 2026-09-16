<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Event;
use App\Models\QrSource;
use App\Services\DashboardMetrics;
use App\Services\LeadQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        protected DashboardMetrics $metrics,
        protected LeadQuery $leadQuery,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = $this->leadQuery->filters($request);
        [$from, $to] = $this->leadQuery->period($filters);

        $events = AnalyticsEvent::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->selectRaw('name, count(*) as total')
            ->groupBy('name')->pluck('total', 'name');

        return view('admin.analytics.index', [
            'filters' => $filters,
            'stats' => $this->metrics->overview($filters),
            'funnel' => $this->metrics->funnel($filters),
            'trend' => $this->metrics->trend($filters),
            'sources' => $this->metrics->bySource($filters),
            'sectors' => $this->metrics->bySector($filters),
            'devices' => $this->metrics->deviceSplit($filters),
            'eventCounts' => collect(AnalyticsEvent::NAMES)->mapWithKeys(fn ($n) => [$n => (int) ($events[$n] ?? 0)]),
            'events' => Event::query()->orderByDesc('is_default')->get(),
            'qrSources' => QrSource::query()->orderBy('name')->get(),
        ]);
    }
}
