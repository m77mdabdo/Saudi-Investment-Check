<x-layouts.admin title="Email templates">
    <x-admin.page-header title="Email templates" subtitle="المتغيرات بتتبدل تلقائيًا وقت الإرسال."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Notifications' => route('admin.notifications.index'), 'Templates' => null]" />

    <div class="ad-card mb-5 p-4">
        <p class="mb-2 text-sm font-bold text-slate-700">المتغيرات المتاحة</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($variables as $variable)
                <code class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-600">&#123;&#123;{{ $variable }}&#125;&#125;</code>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-500">
            مستقبلو إشعار المبيعات الحاليون: <strong>{{ $recipients ? implode('، ', $recipients) : 'غير مضبوط' }}</strong>
            — عدّلهم من <a href="{{ route('admin.settings.edit') }}" class="underline">الإعدادات</a>.
        </p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($templates as $template)
            <section class="ad-card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">{{ $template->audience }}</h2>
                    <x-admin.badge :color="$template->is_active ? 'emerald' : 'slate'">{{ $template->is_active ? 'Active' : 'Off' }}</x-admin.badge>
                </div>

                <form method="POST" action="{{ route('admin.notifications.templates.update', $template) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div><label class="ad-label">اسم القالب</label><input name="name" class="ad-input" required value="{{ $template->name }}"></div>
                    <div><label class="ad-label">عنوان الرسالة</label><input name="subject" class="ad-input" required value="{{ $template->subject }}"></div>
                    <div><label class="ad-label">المحتوى (HTML)</label><textarea name="body" class="ad-textarea font-mono text-xs" rows="12" required>{{ $template->body }}</textarea></div>
                    <label class="ad-check text-sm"><input type="checkbox" name="is_active" value="1" class="accent-[#d9a742]" @checked($template->is_active)> مفعّل</label>
                    <button class="ad-btn ad-btn-primary">حفظ القالب</button>
                </form>

                <form method="POST" action="{{ route('admin.notifications.templates.preview', $template) }}" class="mt-3 flex items-center gap-2 border-t border-line pt-3">
                    @csrf
                    <input name="email" type="email" class="ad-input" dir="ltr" placeholder="ابعت معاينة إلى..." required>
                    <button class="ad-btn ad-btn-ghost">إرسال معاينة</button>
                </form>
            </section>
        @endforeach
    </div>

    <section class="ad-card mt-5 overflow-hidden">
        <h2 class="border-b border-line px-5 py-3 text-sm font-black uppercase tracking-wider text-slate-400">Notification logs</h2>
        @if ($logs->isEmpty())
            <x-admin.empty icon="📨" title="مفيش رسايل اتبعتت لسه" />
        @else
            <div class="ad-table-wrap"><table class="ad-table">
                <thead><tr><th>القالب</th><th>المستقبل</th><th>Lead</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="font-mono text-xs">{{ $log->template_key }}</td>
                            <td class="text-slate-600" dir="ltr">{{ $log->recipient }}</td>
                            <td>@if ($log->lead)<a href="{{ route('admin.leads.show', $log->lead) }}" class="font-bold hover:underline">{{ $log->lead->name }}</a>@else — @endif</td>
                            <td><x-admin.badge :color="$log->status === 'sent' ? 'emerald' : ($log->status === 'failed' ? 'coral' : 'slate')">{{ $log->status }}</x-admin.badge></td>
                            <td class="text-xs text-slate-500" dir="ltr">{{ $log->created_at->format('d M H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        @endif
    </section>
</x-layouts.admin>
