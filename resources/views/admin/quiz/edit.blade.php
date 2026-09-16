@php $isNew = ! $question->exists; @endphp

<x-layouts.admin :title="$isNew ? 'سؤال جديد' : $question->title">
    <x-admin.page-header :title="$isNew ? 'سؤال جديد' : 'تعديل السؤال'" :subtitle="$isNew ? null : $question->key"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Quiz' => route('admin.quiz.index'), ($isNew ? 'جديد' : $question->title) => null]" />

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="ad-card p-5 lg:col-span-1">
            <h2 class="mb-4 text-sm font-black uppercase tracking-wider text-slate-400">تفاصيل السؤال</h2>

            <form method="POST" action="{{ $isNew ? route('admin.quiz.store') : route('admin.quiz.update', $question) }}" class="space-y-3">
                @csrf
                @unless ($isNew) @method('PUT') @endunless

                <div>
                    <label class="ad-label" for="key">المفتاح (key)</label>
                    <input id="key" name="key" class="ad-input font-mono" required value="{{ old('key', $question->key) }}" placeholder="company_stage">
                    @error('key')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="ad-label" for="title">نص السؤال</label>
                    <input id="title" name="title" class="ad-input" required value="{{ old('title', $question->title) }}">
                    @error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="ad-label" for="subtitle">نص مساعد</label>
                    <input id="subtitle" name="subtitle" class="ad-input" value="{{ old('subtitle', $question->subtitle) }}">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ad-label" for="icon">أيقونة</label>
                        <input id="icon" name="icon" class="ad-input text-center" maxlength="8" value="{{ old('icon', $question->icon) }}" placeholder="🏁">
                    </div>
                    <div>
                        <label class="ad-label" for="type">النوع</label>
                        <select id="type" name="type" class="ad-select">
                            @foreach (\App\Models\QuizQuestion::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('type', $question->type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ad-label" for="placeholder">Placeholder</label>
                        <input id="placeholder" name="placeholder" class="ad-input" value="{{ old('placeholder', $question->placeholder) }}">
                    </div>
                    <div>
                        <label class="ad-label" for="position">الترتيب</label>
                        <input id="position" type="number" name="position" class="ad-input" min="1" max="99" value="{{ old('position', $question->position ?: 1) }}">
                    </div>
                </div>

                <div class="space-y-2 rounded-xl bg-slate-50 p-3">
                    <label class="ad-check text-sm"><input type="checkbox" name="is_active" value="1" class="accent-[#d9a742]" @checked(old('is_active', $question->is_active ?? true))> مفعّل</label>
                    <label class="ad-check text-sm"><input type="checkbox" name="is_required" value="1" class="accent-[#d9a742]" @checked(old('is_required', $question->is_required ?? true))> إجباري</label>
                    <label class="ad-check text-sm"><input type="checkbox" name="is_scored" value="1" class="accent-[#d9a742]" @checked(old('is_scored', $question->is_scored ?? true))> يدخل في النقاط</label>
                </div>

                <button class="ad-btn ad-btn-primary w-full">{{ $isNew ? 'إنشاء السؤال' : 'حفظ' }}</button>
            </form>
        </section>

        <section class="lg:col-span-2">
            @if ($isNew)
                <div class="ad-card"><x-admin.empty icon="➕" title="احفظ السؤال الأول" text="بعد الحفظ هتقدر تضيف الاختيارات والنقاط." /></div>
            @else
                <div class="ad-card mb-5 overflow-hidden">
                    <h2 class="border-b border-line px-5 py-3 text-sm font-black uppercase tracking-wider text-slate-400">الاختيارات</h2>

                    @if ($question->options->isEmpty())
                        <x-admin.empty icon="🎛️" title="مفيش اختيارات" text="ضيف أول اختيار من الفورم تحت." />
                    @else
                        <div class="ad-table-wrap"><table class="ad-table">
                            <thead><tr><th>#</th><th>الاختيار</th><th>المفتاح</th><th>النقاط</th><th>تفاصيل إضافية</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($question->options->sortBy('position') as $option)
                                    <tr>
                                        <td class="font-mono text-slate-400">{{ $option->position }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.quiz.options.update', [$question, $option]) }}" class="ad-inline-form">
                                                @csrf @method('PUT')
                                                <input name="icon" class="ad-input w-14 text-center" maxlength="8" value="{{ $option->icon }}">
                                                <input name="label" class="ad-input w-40" required value="{{ $option->label }}">
                                                <input name="description" class="ad-input w-48" value="{{ $option->description }}" placeholder="وصف مختصر">
                                                <input type="hidden" name="key" value="{{ $option->key }}">
                                                <input type="number" name="score" class="ad-input w-20" min="0" max="20" value="{{ $option->score }}">
                                                <label class="ad-check"><input type="checkbox" name="requires_detail" value="1" class="accent-[#d9a742]" @checked($option->requires_detail)> "أخرى"</label>
                                                <input name="detail_label" class="ad-input w-36" value="{{ $option->detail_label }}" placeholder="عنوان الحقل">
                                                <input type="number" name="position" class="ad-input w-16" min="1" max="99" value="{{ $option->position }}" title="الترتيب">
                                                <input type="hidden" name="is_active" value="1">
                                                <button class="ad-btn ad-btn-dark">حفظ</button>
                                            </form>
                                        </td>
                                        <td class="font-mono text-xs text-slate-400">{{ $option->key }}</td>
                                        <td class="font-mono">{{ $option->score }}</td>
                                        <td>@if ($option->requires_detail)<x-admin.badge color="amber">Other</x-admin.badge>@endif</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.quiz.options.destroy', [$question, $option]) }}" x-data="confirmAction('حذف الاختيار؟')" @submit="confirm($event)">
                                                @csrf @method('DELETE')
                                                <button class="ad-btn ad-btn-danger px-2">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>

                <div class="ad-card p-5">
                    <h2 class="mb-3 text-sm font-black uppercase tracking-wider text-slate-400">إضافة اختيار</h2>
                    <form method="POST" action="{{ route('admin.quiz.options.store', $question) }}" class="grid gap-3 sm:grid-cols-6">
                        @csrf
                        <div class="sm:col-span-1"><label class="ad-label">أيقونة</label><input name="icon" class="ad-input text-center" maxlength="8" placeholder="💡"></div>
                        <div class="sm:col-span-2"><label class="ad-label">النص</label><input name="label" class="ad-input" required></div>
                        <div class="sm:col-span-2"><label class="ad-label">المفتاح</label><input name="key" class="ad-input font-mono" required placeholder="idea"></div>
                        <div class="sm:col-span-1"><label class="ad-label">النقاط</label><input type="number" name="score" class="ad-input" min="0" max="20" value="0" required></div>
                        <div class="sm:col-span-3"><label class="ad-label">وصف</label><input name="description" class="ad-input"></div>
                        <div class="sm:col-span-2"><label class="ad-label">عنوان حقل التفاصيل</label><input name="detail_label" class="ad-input" placeholder="اكتب نشاط الشركة"></div>
                        <div class="flex items-end gap-3 sm:col-span-1">
                            <label class="ad-check"><input type="checkbox" name="requires_detail" value="1" class="accent-[#d9a742]"> "أخرى"</label>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                        <div class="sm:col-span-6"><button class="ad-btn ad-btn-primary">إضافة الاختيار</button></div>
                    </form>
                    @error('key')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
