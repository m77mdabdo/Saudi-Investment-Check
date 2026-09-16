@php
    $tone = match ($lead->result_key) { 'ready' => '#10b981', 'needs_prep' => '#f59e0b', default => '#fb7185' };
    $meta = $lead->statusMeta();
@endphp

<x-layouts.admin :title="$lead->name">
    <x-admin.page-header :title="$lead->name" :subtitle="$lead->company"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Leads' => route('admin.leads.index'), $lead->name => null]">
        <x-slot:actions>
            @if ($lead->whatsappLink())
                <a href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener" class="ad-btn ad-btn-primary">
                    <x-admin.icon name="whatsapp" class="h-4 w-4" /> WhatsApp
                </a>
            @endif
            <a href="tel:{{ $lead->whatsapp }}" class="ad-btn ad-btn-ghost">اتصال</a>
            @if ($lead->email)
                <a href="mailto:{{ $lead->email }}" class="ad-btn ad-btn-ghost">إيميل</a>
            @endif
            <a href="{{ route('admin.leads.index') }}" class="ad-btn ad-btn-ghost"><x-admin.icon name="back" class="h-4 w-4" /> رجوع</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- ───────── Left: result + answers ───────── --}}
        <div class="space-y-5 lg:col-span-2">
            <section class="ad-card p-5">
                <div class="flex flex-wrap items-center gap-5">
                    <div class="relative grid h-28 w-28 place-items-center">
                        <svg viewBox="0 0 120 120" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#eef1f8" stroke-width="12"></circle>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $tone }}" stroke-width="12" stroke-linecap="round"
                                    stroke-dasharray="{{ round(2 * M_PI * 52 * $lead->scorePercent() / 100, 1) }} {{ round(2 * M_PI * 52, 1) }}"></circle>
                        </svg>
                        <div class="text-center">
                            <div class="text-2xl font-black">{{ $lead->score }}</div>
                            <div class="text-[0.7rem] text-slate-400">/ {{ $lead->max_score }}</div>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="ad-inline-form">
                            <x-admin.result-badge :lead="$lead" />
                            <x-admin.badge :color="$meta?->color ?? 'slate'">{{ $meta?->label ?? $lead->sales_status }}</x-admin.badge>
                            @if ($lead->event)<x-admin.badge color="slate">{{ $lead->event->name }}</x-admin.badge>@endif
                        </div>
                        <h2 class="mt-2 text-lg font-black text-slate-900">{{ $lead->rule?->headline ?? '—' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $lead->rule?->main_text }}</p>
                        @if ($lead->mainQuestion())
                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                <strong>أكتر حاجة محتاج يعرفها:</strong> {{ $lead->mainQuestion() }}
                            </p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="ad-card overflow-hidden">
                <h2 class="border-b border-line px-5 py-3 text-sm font-black uppercase tracking-wider text-slate-400">Answers</h2>
                <div class="ad-table-wrap"><table class="ad-table">
                    <thead><tr><th>السؤال</th><th>الإجابة</th><th>النقاط</th></tr></thead>
                    <tbody>
                        @foreach ($lead->answers as $answer)
                            <tr>
                                <td class="text-slate-600">{{ $answer->question_title }}</td>
                                <td class="font-bold text-slate-800">
                                    {{ $answer->answer_label ?: '—' }}
                                    @if ($answer->answer_text)
                                        <span class="block text-xs font-normal text-slate-500">“{{ $answer->answer_text }}”</span>
                                    @endif
                                </td>
                                <td class="font-mono text-slate-500">{{ $answer->score }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </section>

            {{-- Activity timeline --}}
            <section class="ad-card p-5">
                <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">Activity</h2>

                <form method="POST" action="{{ route('admin.leads.notes.store', $lead) }}" class="mb-5">
                    @csrf
                    <label class="ad-label" for="note">أضف ملاحظة داخلية</label>
                    <textarea id="note" name="body" class="ad-textarea" rows="3" required maxlength="2000" placeholder="اتكلمنا معاه، طلب عرض سعر..."></textarea>
                    <button class="ad-btn ad-btn-dark mt-2">حفظ الملاحظة</button>
                </form>

                <ol class="relative space-y-4 border-s border-line ps-5">
                    @forelse ($lead->notes as $note)
                        <li class="relative">
                            <span class="absolute -start-[1.6rem] top-1.5 grid h-3 w-3 place-items-center rounded-full bg-slate-300"></span>
                            <p class="text-sm text-slate-800">{{ $note->body }}</p>
                            <p class="text-xs text-slate-400">{{ $note->user?->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">مفيش نشاط لسه.</li>
                    @endforelse

                    <li class="relative">
                        <span class="absolute -start-[1.6rem] top-1.5 grid h-3 w-3 place-items-center rounded-full bg-[#d9a742]"></span>
                        <p class="text-sm text-slate-800">أكمل التقييم — {{ $lead->score }}/{{ $lead->max_score }}</p>
                        <p class="text-xs text-slate-400" dir="ltr">{{ $lead->created_at->format('d M Y H:i') }}</p>
                    </li>
                </ol>
            </section>
        </div>

        {{-- ───────── Right: contact + meta ───────── --}}
        <div class="space-y-5">
            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Contact</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">الاسم</dt><dd class="font-bold">{{ $lead->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">الشركة</dt><dd class="font-bold">{{ $lead->company }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">WhatsApp</dt><dd class="font-mono" dir="ltr">{{ $lead->whatsapp }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="truncate">{{ $lead->email ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Consent</dt><dd dir="ltr">{{ $lead->consent ? '✅ '.$lead->consent_at?->format('d M H:i') : '—' }}</dd></div>
                </dl>
            </section>

            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Sales</h2>

                <form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="mb-3">
                    @csrf @method('PATCH')
                    <label class="ad-label" for="sales_status">حالة المبيعات</label>
                    <select id="sales_status" name="sales_status" class="ad-select" onchange="this.form.submit()">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->key }}" @selected($lead->sales_status === $status->key)>{{ $status->label }}</option>
                        @endforeach
                    </select>
                </form>

                <form method="POST" action="{{ route('admin.leads.assign', $lead) }}">
                    @csrf @method('PATCH')
                    <label class="ad-label" for="assigned_to">المسؤول</label>
                    <select id="assigned_to" name="assigned_to" class="ad-select" onchange="this.form.submit()">
                        <option value="">غير محدد</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected($lead->assigned_to == $owner->id)>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </form>
            </section>

            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Attribution</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Source</dt><dd class="font-bold">{{ $lead->qrSource?->name ?? $lead->source ?? 'Direct' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Event</dt><dd>{{ $lead->event?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">UTM source</dt><dd>{{ $lead->utm_source ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">UTM medium</dt><dd>{{ $lead->utm_medium ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">UTM campaign</dt><dd>{{ $lead->utm_campaign ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Device</dt><dd>{{ $lead->device ?: '—' }} · {{ $lead->browser ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Created</dt><dd dir="ltr">{{ $lead->created_at->format('d M Y H:i') }}</dd></div>
                </dl>
            </section>

            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Notifications</h2>
                @forelse ($lead->notificationLogs as $log)
                    <div class="flex items-center justify-between gap-2 border-b border-slate-50 py-2 text-sm last:border-0">
                        <span class="min-w-0">
                            <span class="block truncate font-bold text-slate-700">{{ $log->template_key }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $log->recipient }}</span>
                        </span>
                        <x-admin.badge :color="$log->status === 'sent' ? 'emerald' : ($log->status === 'failed' ? 'coral' : 'slate')">{{ $log->status }}</x-admin.badge>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">لم تُرسل إشعارات.</p>
                @endforelse
            </section>

            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" x-data="confirmAction('هتحذف الـLead ده نهائيًا؟')" @submit="confirm($event)">
                    @csrf @method('DELETE')
                    <button class="ad-btn ad-btn-danger w-full">حذف الـLead</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.admin>
