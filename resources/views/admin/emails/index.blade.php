<x-layouts.admin :title="__('admin.email_logs.title')">
    <x-admin.page-header :title="__('admin.email_logs.title')" :subtitle="__('admin.email_logs.subtitle')"
                         :breadcrumbs="[__('admin.nav.dashboard') => route('admin.dashboard'), __('admin.email_logs.title') => null]" />

    {{-- ───────── Mail health ───────── --}}
    <section class="ad-card mb-5 p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">Mail health</h2>
            <x-admin.badge :color="$health['ok'] ? 'emerald' : 'coral'">
                {{ $health['ok'] ? 'OK' : count($health['problems']).' issue(s)' }}
            </x-admin.badge>
        </div>

        @if ($health['problems'])
            <ul class="mt-3 space-y-1.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                @foreach ($health['problems'] as $problem)
                    <li>• {{ $problem }}</li>
                @endforeach
            </ul>
        @endif

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <dl class="space-y-1.5 text-sm">
                @foreach ($health['config'] as $label => $value)
                    <div class="flex flex-col gap-0.5 border-b border-slate-50 py-1 last:border-0 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="break-all font-mono text-xs text-slate-800 sm:text-end" dir="ltr">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <dl class="space-y-1.5 text-sm">
                @foreach ($health['settings'] as $label => $value)
                    <div class="flex flex-col gap-0.5 border-b border-slate-50 py-1 last:border-0 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="break-all font-mono text-xs text-slate-800 sm:text-end" dir="ltr">{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="pt-1">
                    @foreach ($health['connection'] as $line)
                        <p class="break-all font-mono text-xs text-slate-600" dir="ltr">{{ $line }}</p>
                    @endforeach
                </div>
            </dl>
        </div>

        {{-- Test send (managers and admins only) --}}
        @can('manage-platform')
        <form method="POST" action="{{ route('admin.emails.test') }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-line pt-4">
            @csrf
            <div class="min-w-48 flex-1">
                <label class="ad-label" for="test-email">{{ __('admin.email_logs.send_test') }}</label>
                <input id="test-email" name="email" type="email" class="ad-input" dir="ltr" required placeholder="you@example.com">
            </div>
            <div>
                <label class="ad-label" for="test-locale">{{ __('common.language') }}</label>
                <select id="test-locale" name="locale" class="ad-select w-auto">
                    @foreach (\App\Support\Locale::all() as $code => $meta)
                        <option value="{{ $code }}">{{ $meta['native'] }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ad-btn ad-btn-primary">{{ __('admin.email_logs.send_test') }}</button>
        </form>
        @endcan
    </section>

    {{-- ───────── Stats ───────── --}}
    <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-admin.stat label="Sent" :value="number_format($stats['sent'])" tone="emerald" icon="mail" />
        <x-admin.stat label="Failed" :value="number_format($stats['failed'])" tone="coral" icon="mail" />
        <x-admin.stat label="Skipped" :value="number_format($stats['skipped'])" tone="slate" icon="mail" />
        <x-admin.stat label="Today" :value="number_format($stats['today'])" tone="blue" icon="mail" />
    </section>

    {{-- ───────── Filters ───────── --}}
    <form method="GET" action="{{ route('admin.emails.index') }}" class="ad-card mb-5 flex flex-wrap items-center gap-2 p-3 sm:p-4">
        <select name="status" class="ad-select w-auto" onchange="this.form.submit()">
            <option value="">{{ __('common.all') }}</option>
            @foreach (['sent', 'failed', 'skipped'] as $status)
                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
            @endforeach
        </select>

        <select name="template" class="ad-select w-auto" onchange="this.form.submit()">
            <option value="">{{ __('admin.email_logs.type') }}</option>
            @foreach ($templates as $template)
                <option value="{{ $template }}" @selected($filters['template'] === $template)>{{ $template }}</option>
            @endforeach
        </select>

        <input type="search" name="q" value="{{ $filters['search'] }}" class="ad-input w-auto min-w-40" placeholder="{{ __('common.search') }}...">
        <button class="ad-btn ad-btn-dark">{{ __('common.apply') }}</button>
        <a href="{{ route('admin.emails.index') }}" class="ad-btn ad-btn-ghost">{{ __('common.clear') }}</a>
    </form>

    {{-- ───────── Log table ───────── --}}
    <div class="ad-card overflow-hidden">
        @if ($logs->isEmpty())
            <x-admin.empty icon="📨" :title="__('admin.email_logs.empty')" />
        @else
            <div class="ad-table-wrap"><table class="ad-table">
                    <thead>
                        <tr>
                            <th>{{ __('admin.email_logs.recipient') }}</th>
                            <th>{{ __('admin.email_logs.subject') }}</th>
                            <th>{{ __('admin.email_logs.type') }}</th>
                            <th>Lead</th>
                            <th>{{ __('admin.email_logs.status') }}</th>
                            <th>{{ __('admin.email_logs.sent_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="font-mono text-xs" dir="ltr">{{ $log->recipient }}</td>
                                <td class="max-w-64 truncate text-slate-700" title="{{ $log->subject }}">{{ $log->subject ?: '—' }}</td>
                                <td><x-admin.badge color="slate">{{ $log->template_key }}</x-admin.badge></td>
                                <td>
                                    @if ($log->lead)
                                        <a href="{{ route('admin.leads.show', $log->lead) }}" class="font-bold hover:underline">{{ $log->lead->name }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <x-admin.badge :color="$log->statusColor()">{{ $log->status }}</x-admin.badge>
                                    @if ($log->error)
                                        <span class="mt-1 block max-w-56 truncate text-[0.7rem] text-rose-600" title="{{ $log->error }}">{{ $log->error }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs text-slate-500" dir="ltr">
                                    {{ optional($log->sent_at ?? $log->failed_at ?? $log->created_at)->format('d M H:i') }}
                                </td>
                                <td class="text-end">
                                    @if ($log->isRetryable() && auth()->user()->canManagePlatform())
                                        <form method="POST" action="{{ route('admin.emails.resend', $log) }}">
                                            @csrf
                                            <button class="ad-btn ad-btn-ghost px-2 text-xs">{{ __('admin.email_logs.resend') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>

            <div class="border-t border-line px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
