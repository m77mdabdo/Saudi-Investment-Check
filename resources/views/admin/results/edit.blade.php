@php $isNew = ! $rule->exists; @endphp

<x-layouts.admin :title="$isNew ? 'نتيجة جديدة' : $rule->headline">
    <x-admin.page-header :title="$isNew ? 'نتيجة جديدة' : 'تعديل النتيجة'" :subtitle="'النقاط القصوى الحالية: '.$maxScore"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Results' => route('admin.results.index'), ($isNew ? 'جديدة' : $rule->key) => null]" />

    <form method="POST" action="{{ $isNew ? route('admin.results.store') : route('admin.results.update', $rule) }}" class="grid gap-5 lg:grid-cols-3">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <section class="ad-card space-y-3 p-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">التصنيف والنطاق</h2>

            <div><label class="ad-label" for="key">المفتاح</label>
                <input id="key" name="key" class="ad-input font-mono" required value="{{ old('key', $rule->key) }}" placeholder="ready">
                @error('key')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div><label class="ad-label" for="classification">التصنيف</label>
                <input id="classification" name="classification" class="ad-input" required value="{{ old('classification', $rule->classification) }}" placeholder="Hot Lead">
            </div>

            <div><label class="ad-label" for="indicator">المؤشر اللوني</label>
                <select id="indicator" name="indicator" class="ad-select">
                    @foreach (['green' => 'أخضر — جاهز', 'amber' => 'كهرماني — محتاج تجهيز', 'coral' => 'مرجاني — بدري'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('indicator', $rule->indicator) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div><label class="ad-label" for="min_score">أقل نقاط</label><input id="min_score" type="number" name="min_score" class="ad-input" min="0" max="100" required value="{{ old('min_score', $rule->min_score ?? 0) }}"></div>
                <div><label class="ad-label" for="max_score">أعلى نقاط</label><input id="max_score" type="number" name="max_score" class="ad-input" min="0" max="100" required value="{{ old('max_score', $rule->max_score ?? $maxScore) }}"></div>
            </div>
            @error('max_score')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

            <div class="grid grid-cols-2 gap-3">
                <div><label class="ad-label" for="position">الترتيب</label><input id="position" type="number" name="position" class="ad-input" min="0" value="{{ old('position', $rule->position ?? 0) }}"></div>
                <label class="mt-6 flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-[#d9a742]" @checked(old('is_active', $rule->is_active ?? true))> مفعّلة</label>
            </div>

            <div><label class="ad-label" for="image_query">كلمات بحث الصورة (Pexels)</label>
                <input id="image_query" name="image_query" class="ad-input" value="{{ old('image_query', $rule->image_query) }}" placeholder="riyadh skyline">
            </div>
        </section>

        <section class="ad-card space-y-3 p-5 lg:col-span-2">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">المحتوى</h2>

            <div><label class="ad-label" for="headline">العنوان الرئيسي</label>
                <input id="headline" name="headline" class="ad-input" required value="{{ old('headline', $rule->headline) }}">
            </div>

            <div><label class="ad-label" for="main_text">النص الأساسي</label>
                <textarea id="main_text" name="main_text" class="ad-textarea" rows="2">{{ old('main_text', $rule->main_text) }}</textarea>
            </div>

            <div><label class="ad-label" for="body">الشرح</label>
                <textarea id="body" name="body" class="ad-textarea" rows="4">{{ old('body', $rule->body) }}</textarea>
            </div>

            <div><label class="ad-label" for="bullets_text">النقاط (سطر لكل نقطة)</label>
                <textarea id="bullets_text" name="bullets_text" class="ad-textarea" rows="4">{{ old('bullets_text', collect($rule->bullets ?? [])->implode("\n")) }}</textarea>
            </div>

            <div><label class="ad-label" for="highlight">الجملة المميزة</label>
                <input id="highlight" name="highlight" class="ad-input" value="{{ old('highlight', $rule->highlight) }}">
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="ad-label" for="primary_cta_label">زر أساسي — النص</label><input id="primary_cta_label" name="primary_cta_label" class="ad-input" value="{{ old('primary_cta_label', $rule->primary_cta_label) }}"></div>
                <div><label class="ad-label" for="primary_cta_url">زر أساسي — الرابط</label><input id="primary_cta_url" name="primary_cta_url" class="ad-input" dir="ltr" value="{{ old('primary_cta_url', $rule->primary_cta_url) }}" placeholder="اتركه فاضي لاستخدام رابط الإعدادات"></div>
                <div><label class="ad-label" for="secondary_cta_label">زر ثانوي — النص</label><input id="secondary_cta_label" name="secondary_cta_label" class="ad-input" value="{{ old('secondary_cta_label', $rule->secondary_cta_label) }}"></div>
                <div><label class="ad-label" for="secondary_cta_url">زر ثانوي — الرابط</label><input id="secondary_cta_url" name="secondary_cta_url" class="ad-input" dir="ltr" value="{{ old('secondary_cta_url', $rule->secondary_cta_url) }}"></div>
            </div>
            @error('primary_cta_url')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

            <div><label class="ad-label" for="disclaimer">إخلاء المسؤولية</label>
                <textarea id="disclaimer" name="disclaimer" class="ad-textarea" rows="2">{{ old('disclaimer', $rule->disclaimer) }}</textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button class="ad-btn ad-btn-primary">{{ $isNew ? 'إنشاء' : 'حفظ التغييرات' }}</button>
                <a href="{{ route('admin.results.index') }}" class="ad-btn ad-btn-ghost">رجوع</a>
            </div>
        </section>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.results.destroy', $rule) }}" class="mt-4" x-data="confirmAction('حذف النتيجة دي؟')" @submit="confirm($event)">
            @csrf @method('DELETE')
            <button class="ad-btn ad-btn-danger">حذف النتيجة</button>
        </form>
    @endunless
</x-layouts.admin>
