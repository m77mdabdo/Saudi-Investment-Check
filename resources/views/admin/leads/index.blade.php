<x-layouts.admin title="Leads">
    <x-admin.page-header title="Leads" :subtitle="'إجمالي '.$leads->total().' — '.($activeFilters ? $activeFilters.' فلتر مفعّل' : 'كل النتائج')"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Leads' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.leads.export', request()->query()) }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="export" class="h-4 w-4" /> تصدير Excel
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filter-bar
        :action="route('admin.leads.index')"
        :filters="$filters"
        :events="$events"
        :qr-sources="$qrSources"
        :statuses="$statuses"
        :sectors="$sectors"
        :timelines="$timelines"
        :owners="$owners"
        :show-search="true"
    />

    <div class="ad-card overflow-hidden">
        @if ($leads->isEmpty())
            <x-admin.empty
                icon="🔍"
                title="No leads yet"
                text="أول ما يبدأ الزوار التقييم، هتظهر النتائج هنا. جرب توسّع الفلاتر أو تعمل QR Source جديد."
                cta-label="إنشاء QR Source"
                :cta-url="route('admin.qr.index')"
            />
        @else
            <div class="overflow-x-auto">
                <table class="ad-table">
                    <thead>
                        <tr>
                            @php
                                $sortLink = fn ($col) => route('admin.leads.index', array_merge(request()->query(), [
                                    'sort' => $col,
                                    'dir' => ($filters['sort'] === $col && $filters['dir'] === 'desc') ? 'asc' : 'desc',
                                ]));
                            @endphp
                            <th><a href="{{ $sortLink('name') }}" class="hover:text-slate-900">Name</a></th>
                            <th>Company</th>
                            <th>WhatsApp</th>
                            <th>Sector</th>
                            <th><a href="{{ $sortLink('score') }}" class="hover:text-slate-900">Score</a></th>
                            <th>Result</th>
                            <th>Source</th>
                            <th>Sales status</th>
                            <th>Owner</th>
                            <th><a href="{{ $sortLink('created_at') }}" class="hover:text-slate-900">Created</a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leads as $lead)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="font-bold text-slate-800 hover:text-[#b9852c]">{{ $lead->name }}</a>
                                </td>
                                <td class="text-slate-600">{{ $lead->company }}</td>
                                <td class="whitespace-nowrap font-mono text-xs text-slate-600" dir="ltr">{{ $lead->whatsapp }}</td>
                                <td class="text-slate-600">{{ data_get($lead->answers_summary, 'sector', '—') }}</td>
                                <td class="whitespace-nowrap"><span class="font-black">{{ $lead->score }}</span><span class="text-xs text-slate-400">/{{ $lead->max_score }}</span></td>
                                <td><x-admin.result-badge :lead="$lead" /></td>
                                <td class="text-slate-600">{{ $lead->qrSource?->name ?? $lead->source ?? 'Direct' }}</td>
                                <td><x-admin.status-select :lead="$lead" :statuses="$statuses" /></td>
                                <td class="text-xs text-slate-500">{{ $lead->owner?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap text-xs text-slate-500" title="{{ $lead->created_at }}">{{ $lead->created_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        @if ($lead->whatsappLink())
                                            <a href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost px-2" title="واتساب">
                                                <x-admin.icon name="whatsapp" class="h-4 w-4 text-emerald-600" />
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="ad-btn ad-btn-ghost px-2" title="فتح">↗</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line px-4 py-3">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
