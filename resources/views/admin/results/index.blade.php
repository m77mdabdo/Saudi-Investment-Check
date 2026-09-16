<x-layouts.admin title="Results">
    <x-admin.page-header title="Result rules" :subtitle="'النطاقات دي بتحدد نتيجة الزائر. أعلى نقاط ممكنة: '.$maxScore"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Results' => null]">
        <x-slot:actions>
            <a href="{{ route('admin.results.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="h-4 w-4" /> نتيجة جديدة</a>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $covered = [];
        foreach ($rules->where('is_active', true) as $rule) {
            for ($i = $rule->min_score; $i <= $rule->max_score; $i++) $covered[$i] = true;
        }
        $gaps = collect(range(0, $maxScore))->reject(fn ($i) => isset($covered[$i]))->values();
    @endphp

    @if ($gaps->isNotEmpty())
        <div class="ad-card mb-5 border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            في نقاط من غير نتيجة مغطية: <strong>{{ $gaps->implode('، ') }}</strong> — الزائر اللي يوصلها مش هيشوف محتوى نتيجة.
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        @foreach ($rules as $rule)
            @php $color = ['green' => 'emerald', 'amber' => 'amber', 'coral' => 'coral'][$rule->indicator] ?? 'slate'; @endphp
            <a href="{{ route('admin.results.edit', $rule) }}" class="ad-card block p-5 transition hover:shadow-md">
                <div class="flex items-center justify-between">
                    <x-admin.badge :color="$color" dot>{{ strtoupper(str_replace('_', ' ', $rule->key)) }}</x-admin.badge>
                    <span class="font-mono text-sm text-slate-500">{{ $rule->min_score }}–{{ $rule->max_score }}</span>
                </div>
                <h2 class="mt-3 text-base font-black text-slate-900">{{ $rule->headline }}</h2>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $rule->main_text }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <x-admin.badge color="slate">{{ $rule->classification }}</x-admin.badge>
                    <x-admin.badge :color="$rule->is_active ? 'emerald' : 'slate'">{{ $rule->is_active ? 'Active' : 'Off' }}</x-admin.badge>
                </div>
            </a>
        @endforeach
    </div>
</x-layouts.admin>
