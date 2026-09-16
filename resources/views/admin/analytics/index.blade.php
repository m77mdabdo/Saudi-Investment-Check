<x-layouts.admin title="Analytics">
    <x-admin.page-header title="Analytics" subtitle="كل خطوة في الرحلة متتبعة من السيرفر."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Analytics' => null]" />

    <x-admin.filter-bar :action="route('admin.analytics')" :filters="$filters" :events="$events" :qr-sources="$qrSources" />

    <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-admin.stat label="Visits" :value="number_format($funnel['visits'])" icon="analytics" tone="blue" />
        <x-admin.stat label="Quiz starts" :value="number_format($funnel['quiz_started'])" icon="quiz" tone="slate" />
        <x-admin.stat label="Completions" :value="number_format($funnel['quiz_completed'])" icon="quiz" tone="slate" :hint="$stats['completion_rate'].'%'" />
        <x-admin.stat label="Leads" :value="number_format($funnel['lead'])" icon="leads" tone="gold" :hint="$stats['conversion'].'% conversion'" />
        <x-admin.stat label="Meetings" :value="number_format($funnel['meeting'])" icon="events" tone="emerald" :hint="$stats['meeting_rate'].'%'" />
    </section>

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="ad-card p-5 lg:col-span-2">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Leads over time</h2>
            <div class="h-64"><canvas id="trendChart" role="img" aria-label="الـleads عبر الوقت"></canvas></div>
        </section>

        <section class="ad-card p-5">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Event counts</h2>
            <ul class="space-y-1.5 text-sm">
                @foreach ($eventCounts as $name => $count)
                    <li class="flex items-center justify-between border-b border-slate-50 py-1.5 last:border-0">
                        <span class="font-mono text-xs text-slate-500">{{ $name }}</span>
                        <span class="font-bold">{{ number_format($count) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="ad-card p-5">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Devices</h2>
            <div class="h-48"><canvas id="deviceChart" role="img" aria-label="توزيع الأجهزة"></canvas></div>
        </section>

        <section class="ad-card p-5">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Sources</h2>
            @forelse ($sources as $source)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 text-sm last:border-0">
                    <span class="text-slate-600">{{ $source['label'] }}</span>
                    <span class="font-bold">{{ $source['total'] }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">مفيش بيانات.</p>
            @endforelse
        </section>

        <section class="ad-card p-5">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Sectors</h2>
            @forelse ($sectors->take(8) as $sector)
                <div class="flex items-center justify-between border-b border-slate-50 py-1.5 text-sm last:border-0">
                    <span class="text-slate-600">{{ $sector['label'] }}</span>
                    <span class="font-bold">{{ $sector['total'] }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">مفيش بيانات.</p>
            @endforelse
        </section>
    </div>

    <script type="application/json" id="chart-data">@json(['trend' => $trend, 'devices' => $devices])</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = JSON.parse(document.getElementById('chart-data').textContent)

            cmChart('trendChart', 'bar', {
                labels: data.trend.labels,
                datasets: [{ label: 'Leads', data: data.trend.values, backgroundColor: '#d9a742', borderRadius: 6, maxBarThickness: 22 }],
            }, { scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef1f8' } }, x: { grid: { display: false } } } })

            cmChart('deviceChart', 'doughnut', {
                labels: Object.keys(data.devices),
                datasets: [{ data: Object.values(data.devices), backgroundColor: ['#0f1c36', '#d9a742', '#94a3b8', '#10b981'], borderWidth: 0, cutout: '65%' }],
            }, { plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } })
        })
    </script>
</x-layouts.admin>
