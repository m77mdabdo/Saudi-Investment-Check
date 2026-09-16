<x-layouts.admin title="QR Sources">
    <x-admin.page-header title="QR Sources" subtitle="كل مصدر له رابط خاص — والـleads بتتربط بيه أوتوماتيك."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'QR Sources' => null]" />

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="ad-card p-5">
            <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">مصدر جديد</h2>
            <form method="POST" action="{{ route('admin.qr.store') }}" class="space-y-3">
                @csrf
                <div><label class="ad-label" for="name">الاسم</label><input id="name" name="name" class="ad-input" required placeholder="Booth QR"></div>
                <div><label class="ad-label" for="slug">Slug</label><input id="slug" name="slug" class="ad-input font-mono" dir="ltr" placeholder="booth_qr"></div>
                <div>
                    <label class="ad-label" for="event_id">الفعالية</label>
                    <select id="event_id" name="event_id" class="ad-select">
                        <option value="">بدون</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected($event->is_default)>{{ $event->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="ad-label" for="campaign">Campaign</label><input id="campaign" name="campaign" class="ad-input" dir="ltr" placeholder="techne_2026"></div>
                    <div><label class="ad-label" for="medium">Medium</label><input id="medium" name="medium" class="ad-input" dir="ltr" placeholder="qr"></div>
                </div>
                <div><label class="ad-label" for="description">وصف</label><input id="description" name="description" class="ad-input"></div>
                <input type="hidden" name="is_active" value="1">
                <button class="ad-btn ad-btn-primary w-full">إنشاء المصدر</button>
                @error('slug')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            </form>
        </section>

        <section class="lg:col-span-2">
            <div class="ad-card overflow-hidden">
                @if ($sources->isEmpty())
                    <x-admin.empty icon="🏷️" title="مفيش مصادر QR" text="اعمل أول مصدر عشان تعرف كل lead جه منين." />
                @else
                    <div class="ad-table-wrap"><table class="ad-table">
                        <thead><tr><th>المصدر</th><th>الرابط</th><th>Leads</th><th>Hot</th><th>الحالة</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($sources as $source)
                                <tr x-data="copyable">
                                    <td>
                                        <span class="block font-bold text-slate-800">{{ $source->name }}</span>
                                        <span class="block font-mono text-xs text-slate-400">{{ $source->slug }}</span>
                                        <span class="block text-xs text-slate-500">{{ $source->event?->name }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="ad-btn ad-btn-ghost max-w-64 truncate font-mono text-xs" dir="ltr"
                                                @click="copy('{{ $source->tracking_url }}')">
                                            <span x-show="!copied">{{ $source->tracking_url }}</span>
                                            <span x-show="copied" x-cloak class="text-emerald-600">تم النسخ ✓</span>
                                        </button>
                                    </td>
                                    <td class="font-bold">{{ $source->leads_count }}</td>
                                    <td class="font-bold text-emerald-600">{{ $source->hot_leads_count }}</td>
                                    <td><x-admin.badge :color="$source->is_active ? 'emerald' : 'slate'">{{ $source->is_active ? 'Active' : 'Off' }}</x-admin.badge></td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.leads.index', ['qr_source_id' => $source->id, 'range' => 'all']) }}" class="ad-btn ad-btn-ghost px-2">Leads</a>
                                            <form method="POST" action="{{ route('admin.qr.toggle', $source) }}">
                                                @csrf @method('PATCH')
                                                <button class="ad-btn ad-btn-ghost px-2">{{ $source->is_active ? '⏸' : '▶' }}</button>
                                            </form>
                                            @if ($source->leads_count === 0)
                                                <form method="POST" action="{{ route('admin.qr.destroy', $source) }}" x-data="confirmAction('حذف المصدر؟')" @submit="confirm($event)">
                                                    @csrf @method('DELETE')
                                                    <button class="ad-btn ad-btn-danger px-2">حذف</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </div>
            <p class="mt-3 text-xs text-slate-500">
                ولّد QR code لأي رابط من الأعلى — الرابط بيحافظ على المصدر طول رحلة الزائر ولحد حفظ الـlead.
            </p>
        </section>
    </div>
</x-layouts.admin>
