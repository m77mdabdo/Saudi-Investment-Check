@props([
    'action',
    'filters' => [],
    'events' => collect(),
    'qrSources' => collect(),
    'statuses' => collect(),
    'sectors' => [],
    'timelines' => [],
    'owners' => collect(),
    'showSearch' => false,
    'exportUrl' => null,
])

@php
    $ranges = \App\Services\LeadQuery::RANGES;
    $active = $filters;
@endphp

<form method="GET" action="{{ $action }}" x-data="{ advanced: {{ collect($filters)->only(['sales_status','qr_source_id','sector','timeline','assigned_to'])->filter()->isNotEmpty() ? 'true' : 'false' }} }"
      class="ad-card mb-5 p-3 sm:p-4">
    <div class="flex flex-wrap items-center gap-2">
        {{-- Date range chips --}}
        <div class="flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1">
            @foreach ($ranges as $key => $label)
                @if ($key !== 'custom')
                    <label class="cursor-pointer rounded-lg px-2.5 py-1.5 text-xs font-bold transition
                                  {{ ($active['range'] ?? '30') === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                        <input type="radio" name="range" value="{{ $key }}" class="sr-only" @checked(($active['range'] ?? '30') === $key) onchange="this.form.submit()">
                        {{ $label }}
                    </label>
                @endif
            @endforeach
            <label class="cursor-pointer rounded-lg px-2.5 py-1.5 text-xs font-bold transition
                          {{ ($active['range'] ?? '') === 'custom' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                <input type="radio" name="range" value="custom" class="sr-only" @checked(($active['range'] ?? '') === 'custom')>
                فترة مخصصة
            </label>
        </div>

        @if (($active['range'] ?? '') === 'custom')
            <input type="date" name="from" value="{{ $active['from'] }}" class="ad-input w-auto" aria-label="من تاريخ">
            <input type="date" name="to" value="{{ $active['to'] }}" class="ad-input w-auto" aria-label="إلى تاريخ">
        @endif

        @if ($events->count() > 1)
            <select name="event_id" class="ad-select w-auto" aria-label="الفعالية" onchange="this.form.submit()">
                <option value="">كل الفعاليات</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(($active['event_id'] ?? null) == $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
        @elseif ($events->count() === 1)
            <input type="hidden" name="event_id" value="{{ $active['event_id'] }}">
        @endif

        <select name="result" class="ad-select w-auto" aria-label="النتيجة" onchange="this.form.submit()">
            <option value="">كل النتائج</option>
            <option value="ready" @selected(($active['result'] ?? null) === 'ready')>READY — Hot</option>
            <option value="needs_prep" @selected(($active['result'] ?? null) === 'needs_prep')>NEEDS PREP — Warm</option>
            <option value="early" @selected(($active['result'] ?? null) === 'early')>EARLY — Early</option>
        </select>

        @if ($showSearch)
            <input type="search" name="q" value="{{ $active['search'] }}" placeholder="بحث..." class="ad-input w-auto min-w-40" aria-label="بحث">
        @endif

        <button type="button" class="ad-btn ad-btn-ghost" @click="advanced = !advanced">
            <span x-text="advanced ? 'إخفاء الفلاتر' : 'فلاتر أكتر'"></span>
        </button>

        <button type="submit" class="ad-btn ad-btn-dark">تطبيق</button>

        <a href="{{ $action }}" class="ad-btn ad-btn-ghost">مسح</a>

        @if ($exportUrl)
            <a href="{{ $exportUrl }}" class="ad-btn ad-btn-primary ms-auto">
                <x-admin.icon name="export" class="h-4 w-4" /> Excel
            </a>
        @endif
    </div>

    <div x-show="advanced" x-cloak class="mt-3 grid gap-2 border-t border-line pt-3 sm:grid-cols-2 lg:grid-cols-5">
        @if ($statuses->isNotEmpty())
            <select name="sales_status" class="ad-select" aria-label="حالة المبيعات">
                <option value="">كل حالات المبيعات</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->key }}" @selected(($active['sales_status'] ?? null) === $status->key)>{{ $status->label }}</option>
                @endforeach
            </select>
        @endif

        @if ($qrSources->isNotEmpty())
            <select name="qr_source_id" class="ad-select" aria-label="مصدر QR">
                <option value="">كل المصادر</option>
                @foreach ($qrSources as $source)
                    <option value="{{ $source->id }}" @selected(($active['qr_source_id'] ?? null) == $source->id)>{{ $source->name }}</option>
                @endforeach
            </select>
        @endif

        @if ($sectors)
            <select name="sector" class="ad-select" aria-label="القطاع">
                <option value="">كل القطاعات</option>
                @foreach ($sectors as $key => $label)
                    <option value="{{ $key }}" @selected(($active['sector'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        @if ($timelines)
            <select name="timeline" class="ad-select" aria-label="التوقيت">
                <option value="">كل التوقيتات</option>
                @foreach ($timelines as $key => $label)
                    <option value="{{ $key }}" @selected(($active['timeline'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        @endif

        @if ($owners->isNotEmpty())
            <select name="assigned_to" class="ad-select" aria-label="المسؤول">
                <option value="">كل المسؤولين</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(($active['assigned_to'] ?? null) == $owner->id)>{{ $owner->name }}</option>
                @endforeach
            </select>
        @endif
    </div>
</form>
