<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Lead;
use App\Models\QuizOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    public function __construct(protected LeadQuery $leadQuery) {}

    /** @param array<string,mixed> $filters */
    public function overview(array $filters): array
    {
        $leads = $this->leadQuery->results($filters);
        $base = (clone $leads);

        $byResult = (clone $base)->selectRaw('result_key, count(*) as total')
            ->groupBy('result_key')->pluck('total', 'result_key');

        $funnel = $this->funnel($filters);
        $total = (int) (clone $base)->count();

        return [
            'total' => $total,
            'ready' => (int) ($byResult['ready'] ?? 0),
            'needs_prep' => (int) ($byResult['needs_prep'] ?? 0),
            'early' => (int) ($byResult['early'] ?? 0),
            'quiz_starts' => $funnel['quiz_started'],
            'quiz_completions' => $funnel['quiz_completed'],
            'meetings' => $funnel['meeting'],
            'conversion' => $funnel['quiz_started'] > 0 ? round(($total / $funnel['quiz_started']) * 100, 1) : 0.0,
            'completion_rate' => $funnel['quiz_started'] > 0 ? round(($funnel['quiz_completed'] / $funnel['quiz_started']) * 100, 1) : 0.0,
            'meeting_rate' => $total > 0 ? round(($funnel['meeting'] / $total) * 100, 1) : 0.0,
            'with_email' => (int) (clone $base)->whereNotNull('email')->count(),
            'unassigned' => (int) (clone $base)->whereNull('assigned_to')->count(),
            'follow_up' => (int) (clone $base)->whereIn('sales_status', ['follow_up', 'contacted'])->count(),
        ];
    }

    /** @return array<string,int> */
    public function funnel(array $filters): array
    {
        $counts = [];

        foreach (['landing_page_view', 'quiz_started', 'quiz_completed', 'lead_submitted', 'meeting_clicked'] as $name) {
            $counts[$name] = (int) $this->analyticsQuery($filters)->where('name', $name)->count();
        }

        $counts['visits'] = $counts['landing_page_view'];
        $counts['lead'] = (int) $this->leadQuery->results($filters)->count();
        $counts['meeting'] = $counts['meeting_clicked'];

        return $counts;
    }

    /** Leads per day for the chart. */
    public function trend(array $filters): array
    {
        [$from, $to] = $this->leadQuery->period($filters);
        $from = $from ? Carbon::parse($from) : (Lead::query()->min('created_at') ? Carbon::parse(Lead::query()->min('created_at')) : now()->subDays(29));
        $to = $to ? Carbon::parse($to) : now();

        if ($from->diffInDays($to) > 120) {
            $from = $to->copy()->subDays(120);
        }

        $rows = $this->leadQuery->results($filters)
            ->selectRaw('DATE(created_at) as day, count(*) as total, sum(case when result_key = "ready" then 1 else 0 end) as ready')
            ->groupBy('day')->pluck('total', 'day');

        $ready = $this->leadQuery->results($filters)
            ->where('result_key', 'ready')
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')->pluck('total', 'day');

        $labels = [];
        $values = [];
        $hot = [];

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $values[] = (int) ($rows[$key] ?? 0);
            $hot[] = (int) ($ready[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'hot' => $hot];
    }

    /** @return Collection<int,array<string,mixed>> */
    public function bySource(array $filters): Collection
    {
        return $this->leadQuery->results($filters)
            ->leftJoin('qr_sources', 'qr_sources.id', '=', 'leads.qr_source_id')
            ->selectRaw('COALESCE(qr_sources.name, leads.source, "Direct") as label, count(*) as total, sum(case when leads.result_key = "ready" then 1 else 0 end) as hot')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total, 'hot' => (int) $row->hot]);
    }

    /** @return Collection<int,array<string,mixed>> */
    public function bySector(array $filters): Collection
    {
        $labels = QuizOption::query()
            ->whereHas('question', fn (Builder $q) => $q->where('key', 'sector'))
            ->pluck('label', 'key');

        return $this->leadQuery->results($filters)
            ->join('lead_answers', 'lead_answers.lead_id', '=', 'leads.id')
            ->where('lead_answers.question_key', 'sector')
            ->selectRaw('lead_answers.option_key as k, count(*) as total')
            ->groupBy('k')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key' => $row->k,
                'label' => $labels[$row->k] ?? $row->k ?? '—',
                'total' => (int) $row->total,
            ]);
    }

    /** @return Collection<int,array<string,mixed>> */
    public function byStatus(array $filters): Collection
    {
        return $this->leadQuery->results($filters)
            ->selectRaw('sales_status, count(*) as total')
            ->groupBy('sales_status')
            ->pluck('total', 'sales_status')
            ->map(fn ($v) => (int) $v)
            ->collect()
            ->map(fn ($total, $key) => ['key' => $key, 'total' => $total])
            ->values();
    }

    public function deviceSplit(array $filters): array
    {
        return $this->leadQuery->results($filters)
            ->selectRaw('COALESCE(device, "unknown") as d, count(*) as total')
            ->groupBy('d')->pluck('total', 'd')->map(fn ($v) => (int) $v)->all();
    }

    protected function analyticsQuery(array $filters): Builder
    {
        [$from, $to] = $this->leadQuery->period($filters);

        return AnalyticsEvent::query()
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))
            ->when($filters['qr_source_id'] ?? null, fn (Builder $q, $id) => $q->where('qr_source_id', $id));
    }
}
