<x-layouts.admin title="Quiz builder">
    <x-admin.page-header title="Quiz builder" :subtitle="'الحد الأقصى للنقاط حاليًا: '.$maxScore.' نقطة — النتائج بتتحسب من السيرفر دايمًا.'"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Quiz' => null]">
        <x-slot:actions>
            <a href="{{ route('quiz') }}" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost">معاينة الاختبار ↗</a>
            <a href="{{ route('admin.quiz.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="h-4 w-4" /> سؤال جديد</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($maxScore !== (int) config('creativemark.quiz.max_score'))
        <div class="ad-card mb-5 border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            الحد الأقصى للنقاط ({{ $maxScore }}) مختلف عن نطاقات النتائج المعرّفة. راجع
            <a href="{{ route('admin.results.index') }}" class="font-bold underline">Results</a> عشان تغطي كل النطاق.
        </div>
    @endif

    <div class="ad-card overflow-hidden">
        @if ($questions->isEmpty())
            <x-admin.empty icon="🧩" title="مفيش أسئلة لسه" text="ابدأ بإضافة أول سؤال للاختبار." cta-label="سؤال جديد" :cta-url="route('admin.quiz.create')" />
        @else
            <table class="ad-table">
                <thead>
                    <tr><th>#</th><th>السؤال</th><th>النوع</th><th>الاختيارات</th><th>أعلى نقاط</th><th>الحالة</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($questions as $question)
                        <tr>
                            <td class="font-mono text-slate-400">{{ $question->position }}</td>
                            <td>
                                <a href="{{ route('admin.quiz.edit', $question) }}" class="font-bold text-slate-800 hover:text-[#b9852c]">
                                    {{ $question->icon }} {{ $question->title }}
                                </a>
                                <span class="block font-mono text-xs text-slate-400">{{ $question->key }}</span>
                            </td>
                            <td><x-admin.badge color="slate">{{ $question->type }}</x-admin.badge></td>
                            <td class="text-slate-600">{{ $question->options->count() }}</td>
                            <td class="font-mono">{{ $question->is_scored ? (int) $question->options->max('score') : '—' }}</td>
                            <td>
                                <x-admin.badge :color="$question->is_active ? 'emerald' : 'slate'">{{ $question->is_active ? 'Active' : 'Off' }}</x-admin.badge>
                                @if ($question->is_required)<x-admin.badge color="blue">Required</x-admin.badge>@endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <form method="POST" action="{{ route('admin.quiz.toggle', $question) }}">
                                        @csrf @method('PATCH')
                                        <button class="ad-btn ad-btn-ghost px-2" title="تفعيل/إيقاف">{{ $question->is_active ? '⏸' : '▶' }}</button>
                                    </form>
                                    <a href="{{ route('admin.quiz.edit', $question) }}" class="ad-btn ad-btn-ghost px-2">تعديل</a>
                                    <form method="POST" action="{{ route('admin.quiz.destroy', $question) }}" x-data="confirmAction('هتحذف السؤال وكل اختياراته؟')" @submit="confirm($event)">
                                        @csrf @method('DELETE')
                                        <button class="ad-btn ad-btn-danger px-2">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <p class="mt-3 text-xs text-slate-500">الترتيب بيتحدد من خانة الترتيب داخل كل سؤال.</p>
</x-layouts.admin>
