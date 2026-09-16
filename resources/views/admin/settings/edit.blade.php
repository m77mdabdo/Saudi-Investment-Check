<x-layouts.admin title="Settings">
    <x-admin.page-header title="Settings" subtitle="روابط الـCTA، الإشعارات، وحالات المبيعات."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Settings' => null]" />

    <div class="grid gap-5 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5 lg:col-span-2">
            @csrf @method('PUT')

            @foreach ($groups as $group => $settings)
                <section class="ad-card p-5">
                    <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">{{ ucfirst($group) }}</h2>
                    <div class="space-y-4">
                        @foreach ($settings as $setting)
                            <div>
                                @if ($setting->type === 'bool')
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" class="h-4 w-4 accent-[#d9a742]" @checked(filter_var($setting->value, FILTER_VALIDATE_BOOL))>
                                        {{ $setting->label ?? $setting->key }}
                                    </label>
                                @elseif ($setting->type === 'text')
                                    <label class="ad-label" for="s-{{ $setting->key }}">{{ $setting->label ?? $setting->key }}</label>
                                    <textarea id="s-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="ad-textarea" rows="3">{{ $setting->value }}</textarea>
                                @else
                                    <label class="ad-label" for="s-{{ $setting->key }}">{{ $setting->label ?? $setting->key }}</label>
                                    <input id="s-{{ $setting->key }}" name="settings[{{ $setting->key }}]" class="ad-input" dir="{{ $setting->type === 'url' ? 'ltr' : 'auto' }}" value="{{ $setting->value }}">
                                @endif
                                @if ($setting->hint)<p class="mt-1 text-xs text-slate-400">{{ $setting->hint }}</p>@endif
                                @error('settings.'.$setting->key)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                @error($setting->key)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <button class="ad-btn ad-btn-primary">حفظ الإعدادات</button>
        </form>

        <div class="space-y-5">
            {{-- Sales statuses --}}
            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Sales statuses</h2>

                <div class="space-y-2">
                    @foreach ($statuses as $status)
                        <form method="POST" action="{{ route('admin.settings.statuses.update', $status) }}" class="flex flex-wrap items-center gap-1.5 rounded-lg border border-line p-2">
                            @csrf @method('PUT')
                            <input name="label" class="ad-input h-9 min-h-9 w-28 py-1 text-sm" value="{{ $status->label }}">
                            <select name="color" class="ad-select h-9 min-h-9 w-auto py-1 text-xs">
                                @foreach (['blue', 'indigo', 'violet', 'amber', 'emerald', 'gold', 'slate', 'coral'] as $color)
                                    <option value="{{ $color }}" @selected($status->color === $color)>{{ $color }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="position" class="ad-input h-9 min-h-9 w-16 py-1 text-xs" value="{{ $status->position }}">
                            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" class="h-3.5 w-3.5 accent-[#d9a742]" @checked($status->is_active)> نشط</label>
                            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_default" value="1" class="h-3.5 w-3.5 accent-[#d9a742]" @checked($status->is_default)> افتراضي</label>
                            <button class="ad-btn ad-btn-ghost px-2 text-xs">حفظ</button>
                        </form>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('admin.settings.statuses.store') }}" class="mt-3 flex items-center gap-2">
                    @csrf
                    <input name="label" class="ad-input" placeholder="حالة جديدة" required>
                    <select name="color" class="ad-select w-auto">
                        @foreach (['blue', 'indigo', 'violet', 'amber', 'emerald', 'gold', 'slate', 'coral'] as $color)
                            <option value="{{ $color }}">{{ $color }}</option>
                        @endforeach
                    </select>
                    <button class="ad-btn ad-btn-dark">إضافة</button>
                </form>
            </section>

            {{-- Environment health --}}
            <section class="ad-card p-5">
                <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">Integrations</h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center justify-between">
                        <span class="text-slate-600">Mail ({{ $mail['mailer'] }})</span>
                        <x-admin.badge :color="$mail['configured'] ? 'emerald' : 'amber'">{{ $mail['configured'] ? 'Configured' : 'Check .env' }}</x-admin.badge>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-slate-600">Pexels</span>
                        <x-admin.badge :color="$integrations['pexels'] ? 'emerald' : 'slate'">{{ $integrations['pexels'] ? 'Configured' : 'Fallback images' }}</x-admin.badge>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-slate-600">Google OAuth</span>
                        <x-admin.badge :color="$integrations['google'] ? 'emerald' : 'slate'">{{ $integrations['google'] ? 'Configured' : 'Disabled' }}</x-admin.badge>
                    </li>
                </ul>
                <p class="mt-3 text-xs text-slate-400">القيم الحساسة بتتقرا من ملف البيئة فقط ومش بتتعرض هنا.</p>
            </section>
        </div>
    </div>
</x-layouts.admin>
