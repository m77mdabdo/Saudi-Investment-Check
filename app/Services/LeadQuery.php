<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Shared, whitelisted filtering for the leads list, analytics and exports. */
class LeadQuery
{
    public const RANGES = ['today' => 'اليوم', '7' => 'آخر 7 أيام', '30' => 'آخر 30 يوم', 'all' => 'كل الوقت', 'custom' => 'فترة مخصصة'];

    /** @return array<string,mixed> */
    public function filters(Request $request): array
    {
        return [
            'range' => in_array($request->query('range'), array_keys(self::RANGES), true) ? $request->query('range') : '30',
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
            'event_id' => $request->filled('event_id') ? (int) $request->query('event_id') : null,
            'result' => in_array($request->query('result'), ['ready', 'needs_prep', 'early'], true) ? $request->query('result') : null,
            'sales_status' => $request->filled('sales_status') ? (string) $request->query('sales_status') : null,
            'qr_source_id' => $request->filled('qr_source_id') ? (int) $request->query('qr_source_id') : null,
            'sector' => $request->filled('sector') ? (string) $request->query('sector') : null,
            'timeline' => $request->filled('timeline') ? (string) $request->query('timeline') : null,
            'assigned_to' => $request->filled('assigned_to') ? (int) $request->query('assigned_to') : null,
            'search' => $request->filled('q') ? (string) $request->query('q') : null,
            'sort' => in_array($request->query('sort'), ['created_at', 'score', 'name'], true) ? $request->query('sort') : 'created_at',
            'dir' => $request->query('dir') === 'asc' ? 'asc' : 'desc',
        ];
    }

    /** @param array<string,mixed> $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        [$from, $to] = $this->period($filters);

        if ($from) {
            $query->where('leads.created_at', '>=', $from);
        }

        if ($to) {
            $query->where('leads.created_at', '<=', $to);
        }

        return $query
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))
            ->when($filters['result'] ?? null, fn (Builder $q, $r) => $q->where('result_key', $r))
            ->when($filters['sales_status'] ?? null, fn (Builder $q, $s) => $q->where('sales_status', $s))
            ->when($filters['qr_source_id'] ?? null, fn (Builder $q, $id) => $q->where('qr_source_id', $id))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when($filters['sector'] ?? null, fn (Builder $q, $sector) => $q->whereHas('answers', fn ($a) => $a->where('question_key', 'sector')->where('option_key', $sector)))
            ->when($filters['timeline'] ?? null, fn (Builder $q, $t) => $q->whereHas('answers', fn ($a) => $a->where('question_key', 'timeline')->where('option_key', $t)))
            ->when($filters['search'] ?? null, fn (Builder $q, $term) => $q->search($term));
    }

    public function results(array $filters): Builder
    {
        return $this->apply(Lead::query(), $filters);
    }

    /** @return array{0:?Carbon,1:?Carbon} */
    public function period(array $filters): array
    {
        $range = $filters['range'] ?? '30';

        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'custom' => [
                $filters['from'] ? Carbon::parse($filters['from'])->startOfDay() : null,
                $filters['to'] ? Carbon::parse($filters['to'])->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    public function activeCount(array $filters): int
    {
        return collect($filters)
            ->only(['event_id', 'result', 'sales_status', 'qr_source_id', 'sector', 'timeline', 'assigned_to', 'search'])
            ->filter()
            ->count() + (($filters['range'] ?? '30') !== '30' ? 1 : 0);
    }

    protected function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
