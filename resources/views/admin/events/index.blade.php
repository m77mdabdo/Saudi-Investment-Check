<x-layouts.admin title="Events">
    <x-admin.page-header title="Events" subtitle="كل lead بينتمي لفعالية — والداشبورد بتفلتر بيها."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Events' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.events.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="h-4 w-4" /> فعالية جديدة</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="ad-card overflow-hidden">
        @if ($events->isEmpty())
            <x-admin.empty icon="📅" title="مفيش فعاليات" text="ابدأ بإضافة فعالية TECHNE." cta-label="فعالية جديدة" :cta-url="route('admin.events.create')" />
        @else
            <div class="ad-table-wrap"><table class="ad-table">
                <thead><tr><th>الاسم</th><th>المدينة</th><th>التواريخ</th><th>الحالة</th><th>Leads</th><th></th></tr></thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('admin.events.edit', $event) }}" class="font-bold text-slate-800 hover:text-[#b9852c]">{{ $event->name }}</a>
                                @if ($event->is_default)<x-admin.badge color="gold">Default</x-admin.badge>@endif
                            </td>
                            <td class="text-slate-600">{{ trim($event->city.($event->country ? '، '.$event->country : '')) ?: '—' }}</td>
                            <td class="text-slate-600">{{ $event->date_range ?: '—' }}</td>
                            <td><x-admin.badge :color="$event->status === 'active' ? 'emerald' : ($event->status === 'upcoming' ? 'blue' : 'slate')">{{ $event->status }}</x-admin.badge></td>
                            <td class="font-bold">{{ $event->leads_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.events.edit', $event) }}" class="ad-btn ad-btn-ghost px-2">تعديل</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        @endif
    </div>
</x-layouts.admin>
