<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lead;
use App\Models\QrSource;
use App\Services\DashboardMetrics;
use App\Services\LeadQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardMetrics $metrics,
        protected LeadQuery $leadQuery,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = $this->leadQuery->filters($request);

        return view('admin.dashboard', [
            'filters' => $filters,
            'stats' => $this->metrics->overview($filters),
            'funnel' => $this->metrics->funnel($filters),
            'trend' => $this->metrics->trend($filters),
            'sources' => $this->metrics->bySource($filters),
            'sectors' => $this->metrics->bySector($filters),
            'statuses' => $this->metrics->byStatus($filters),
            'events' => Event::query()->orderByDesc('is_default')->get(),
            'qrSources' => QrSource::query()->orderBy('name')->get(),
            'recent' => $this->leadQuery->results($filters)
                ->with(['qrSource', 'event'])
                ->latest()->limit(8)->get(),
            'hotLeads' => $this->leadQuery->results($filters)
                ->where('result_key', 'ready')
                ->whereIn('sales_status', ['new', 'contacted'])
                ->latest()->limit(5)->get(),
            'totalLeadsAllTime' => Lead::query()->count(),
        ]);
    }
}
