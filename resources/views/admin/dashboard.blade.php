<x-layouts.admin title="Dashboard">
    <x-admin.page-header title="Dashboard" :subtitle="'نظرة سريعة على أداء الـSaudi-Ready Check' . ($currentEvent ? ' — ' . $currentEvent->name : '')">
        <x-slot:actions>
            <a href="{{ route('admin.leads.index', ['result' => 'ready']) }}" class="ad-btn ad-btn-ghost">
                <x-admin.icon name="flame" class="h-4 w-4 text-rose-500" /> Hot leads ({{ $stats['ready'] }})
            </a>
            <a href="{{ route('admin.leads.export', request()->query()) }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="export" class="h-4 w-4" /> تصدير Excel
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filter-bar :action="route('admin.dashboard')" :filters="$filters" :events="$events" :qr-sources="$qrSources" />

    @if ($totalLeadsAllTime === 0)
        <div class="ad-card">
            <x-admin.empty
                icon="📡"
                title="No leads yet"
                text="أول ما يبدأ الزوار التقييم، هتظهر النتائج هنا."
                cta-label="إنشاء QR Source"
                :cta-url="route('admin.qr.index')"
            >
                <a href="{{ route('admin.quiz.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-800">أو راجع إعداد الاختبار</a>
            </x-admin.empty>
        </div>
    @else
        {{-- ───────── 1. Overview ───────── --}}
        <section class="mb-6">
            <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Overview</h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <x-admin.stat label="Total leads" :value="number_format($stats['total'])" icon="leads" tone="blue" :href="route('admin.leads.index', request()->query())" />
                <x-admin.stat label="Hot leads" :value="number_format($stats['ready'])" icon="flame" tone="emerald" hint="READY — 9 إلى 12" :href="route('admin.leads.index', array_merge(request()->query(), ['result' => 'ready']))" />
                <x-admin.stat label="Warm leads" :value="number_format($stats['needs_prep'])" tone="amber" icon="dot" hint="NEEDS PREP — 5 إلى 8" :href="route('admin.leads.index', array_merge(request()->query(), ['result' => 'needs_prep']))" />
                <x-admin.stat label="Early leads" :value="number_format($stats['early'])" tone="coral" icon="dot" hint="EARLY — 0 إلى 4" :href="route('admin.leads.index', array_merge(request()->query(), ['result' => 'early']))" />
                <x-admin.stat label="Quiz starts" :value="number_format($stats['quiz_starts'])" tone="slate" icon="quiz" />
                <x-admin.stat label="Quiz completions" :value="number_format($stats['quiz_completions'])" tone="slate" icon="quiz" :hint="$stats['completion_rate'].'% completion'" />
                <x-admin.stat label="Meetings" :value="number_format($stats['meetings'])" tone="gold" icon="events" :hint="$stats['meeting_rate'].'% من إجمالي الـ leads'" />
                <x-admin.stat label="Conversion rate" :value="$stats['conversion'].'%'" tone="gold" icon="analytics" hint="من بداية الاختبار إلى lead" />
            </div>
        </section>

        <div class="grid gap-5 lg:grid-cols-3">
            {{-- ───────── 2. Funnel ───────── --}}
            <section class="ad-card p-5">
                <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Funnel</h2>
                @php
                    $steps = [
                        ['Visits', $funnel['visits']],
                        ['Quiz started', $funnel['quiz_started']],
                        ['Quiz completed', $funnel['quiz_completed']],
                        ['Lead submitted', $funnel['lead']],
                        ['Meeting', $funnel['meeting']],
                    ];
                    $top = max(1, $funnel['visits'] ?: $funnel['quiz_started'] ?: 1);
                @endphp
                <ol class="space-y-2">
                    @foreach ($steps as $i => $step)
                        @php
                            [$label, $value] = $step;
                            $width = max(12, (int) round(($value / $top) * 100));
                        @endphp
                        <li>
                            <div class="ad-funnel-step" style="width: {{ $width }}%; min-width: 8rem">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-bold">{{ $label }}</span>
                                    <span class="font-black">{{ number_format($value) }}</span>
                                </div>
                            </div>
                            @if ($i < count($steps) - 1)
                                <div class="my-1 ps-4 text-xs text-slate-400" aria-hidden="true">↓</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- ───────── 3. Trend ───────── --}}
            <section class="ad-card p-5 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">Leads trend</h2>
                    <span class="text-xs text-slate-400">{{ \App\Services\LeadQuery::RANGES[$filters['range']] ?? '' }}</span>
                </div>
                <div class="h-64"><canvas id="trendChart" aria-label="مخطط الـleads عبر الوقت" role="img"></canvas></div>
            </section>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-3">
            {{-- ───────── 4. Classification ───────── --}}
            <section class="ad-card p-5">
                <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Lead classification</h2>
                <div class="h-52"><canvas id="classChart" role="img" aria-label="توزيع التصنيفات"></canvas></div>
                @php
                    $classRows = [
                        ['READY', $stats['ready'], '#10b981'],
                        ['NEEDS PREP', $stats['needs_prep'], '#f59e0b'],
                        ['EARLY', $stats['early'], '#fb7185'],
                    ];
                @endphp
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach ($classRows as $row)
                        @php [$label, $value, $color] = $row; @endphp
                        <li class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-600">
                                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $color }}"></span>{{ $label }}
                            </span>
                            <span class="font-bold">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- ───────── 5. Sources ───────── --}}
            <section class="ad-card p-5">
                <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Sources</h2>
                @forelse ($sources as $source)
                    @php $share = $stats['total'] ? round(($source['total'] / $stats['total']) * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-700">{{ $source['label'] }}</span>
                            <span class="text-slate-500">{{ $source['total'] }} <span class="text-xs text-emerald-600">({{ $source['hot'] }} hot)</span></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gradient-to-l from-[#e4bd68] to-[#d9a742]" style="width: {{ max(3, $share) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-admin.empty icon="🏷️" title="مفيش مصادر لسه" text="ابدأ بإنشاء QR Source للفعالية." :cta-url="route('admin.qr.index')" cta-label="QR Sources" />
                @endforelse
            </section>

            {{-- ───────── 6. Sectors ───────── --}}
            <section class="ad-card p-5">
                <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Sectors</h2>
                @forelse ($sectors->take(8) as $sector)
                    @php $share = $stats['total'] ? round(($sector['total'] / $stats['total']) * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <a href="{{ route('admin.leads.index', array_merge(request()->query(), ['sector' => $sector['key']])) }}" class="font-bold text-slate-700 hover:underline">{{ $sector['label'] }}</a>
                            <span class="text-slate-500">{{ $sector['total'] }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gradient-to-l from-blue-400 to-blue-600" style="width: {{ max(3, $share) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-admin.empty icon="🧭" title="لسه مفيش قطاعات" text="هتظهر هنا بعد أول Lead." />
                @endforelse
            </section>
        </div>

        {{-- ───────── 7. Recent leads ───────── --}}
        <section class="ad-card mt-5 overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-3">
                <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">Recent leads</h2>
                <a href="{{ route('admin.leads.index', request()->query()) }}" class="text-sm font-bold text-slate-600 hover:text-slate-900">عرض الكل →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Name</th><th>Company</th><th>Score</th><th>Result</th>
                            <th>Source</th><th>Status</th><th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $lead)
                            <tr class="cursor-pointer" onclick="window.location='{{ route('admin.leads.show', $lead) }}'">
                                <td class="font-bold text-slate-800">{{ $lead->name }}</td>
                                <td class="text-slate-600">{{ $lead->company }}</td>
                                <td><span class="font-black">{{ $lead->score }}</span><span class="text-xs text-slate-400">/{{ $lead->max_score }}</span></td>
                                <td><x-admin.result-badge :lead="$lead" /></td>
                                <td class="text-slate-600">{{ $lead->qrSource?->name ?? $lead->source ?? 'Direct' }}</td>
                                <td>
                                    @php $meta = $lead->statusMeta(); @endphp
                                    <x-admin.badge :color="$meta?->color ?? 'slate'">{{ $meta?->label ?? $lead->sales_status }}</x-admin.badge>
                                </td>
                                <td class="whitespace-nowrap text-xs text-slate-500">{{ $lead->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ───────── Follow-up queue ───────── --}}
        @if ($hotLeads->isNotEmpty())
            <section class="ad-card mt-5 p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Hot leads تحتاج متابعة</h2>
                <ul class="divide-y divide-slate-100">
                    @foreach ($hotLeads as $lead)
                        <li class="flex flex-wrap items-center gap-3 py-2.5">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="font-bold text-slate-800 hover:underline">{{ $lead->name }}</a>
                            <span class="text-sm text-slate-500">{{ $lead->company }}</span>
                            <x-admin.result-badge :lead="$lead" />
                            <div class="ms-auto flex items-center gap-2">
                                @if ($lead->whatsappLink())
                                    <a href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost">
                                        <x-admin.icon name="whatsapp" class="h-4 w-4 text-emerald-600" /> WhatsApp
                                    </a>
                                @endif
                                <a href="{{ route('admin.leads.show', $lead) }}" class="ad-btn ad-btn-dark">فتح</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @php
            $chartData = [
                'trend' => $trend,
                'classes' => [$stats['ready'], $stats['needs_prep'], $stats['early']],
            ];
        @endphp
        <script type="application/json" id="chart-data">@json($chartData)</script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const data = JSON.parse(document.getElementById('chart-data').textContent)

                cmChart('trendChart', 'line', {
                    labels: data.trend.labels,
                    datasets: [
                        { label: 'Leads', data: data.trend.values, borderColor: '#d9a742', backgroundColor: 'rgba(217,167,66,.14)', fill: true, tension: .35, borderWidth: 2, pointRadius: 0 },
                        { label: 'Hot', data: data.trend.hot, borderColor: '#10b981', backgroundColor: 'transparent', tension: .35, borderWidth: 2, pointRadius: 0, borderDash: [4, 4] },
                    ],
                }, {
                    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef1f8' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 10, maxRotation: 0, autoSkip: true } } },
                })

                cmChart('classChart', 'doughnut', {
                    labels: ['READY', 'NEEDS PREP', 'EARLY'],
                    datasets: [{ data: data.classes, backgroundColor: ['#10b981', '#f59e0b', '#fb7185'], borderWidth: 0, cutout: '68%' }],
                }, { plugins: { legend: { display: false } } })
            })
        </script>
    @endif
</x-layouts.admin>
