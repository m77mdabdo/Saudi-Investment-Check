@php
    $get = fn ($key, $default = '') => old("content.$key", data_get($content, $key, $default));
    $benefits = old('benefits', data_get($content, 'benefits', [])) ?: [['icon' => '', 'title' => '', 'text' => '']];
@endphp

<x-layouts.admin title="CMS">
    <x-admin.page-header title="محتوى الصفحة العامة" subtitle="كل النصوص دي بتظهر للزائر — من غير أي تعديل في الكود."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'CMS' => null]">
        <x-slot:actions>
            <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="ad-btn ad-btn-ghost">معاينة ↗</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.cms.update') }}" class="grid gap-5 lg:grid-cols-2">
        @csrf @method('PUT')

        <section class="ad-card space-y-3 p-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">Hero</h2>
            <div><label class="ad-label">Eyebrow</label><input name="content[eyebrow]" class="ad-input" value="{{ $get('eyebrow') }}"></div>
            <div><label class="ad-label">العنوان الكبير</label><input name="content[hero_kicker]" class="ad-input" value="{{ $get('hero_kicker') }}"></div>
            <div><label class="ad-label">السطر التمهيدي</label><input name="content[hero_lead]" class="ad-input" value="{{ $get('hero_lead') }}"></div>
            <div><label class="ad-label">السؤال الرئيسي</label><input name="content[hero_title]" class="ad-input" value="{{ $get('hero_title') }}"></div>
            <div><label class="ad-label">الوصف (سطر لكل جملة)</label><textarea name="content[hero_description]" class="ad-textarea" rows="4">{{ $get('hero_description') }}</textarea></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ad-label">المؤشر الصغير</label><input name="content[hero_meta]" class="ad-input" value="{{ $get('hero_meta') }}"></div>
                <div><label class="ad-label">نص زر البداية</label><input name="content[cta_label]" class="ad-input" value="{{ $get('cta_label') }}"></div>
            </div>
            <div><label class="ad-label">كلمات بحث صورة الهيرو (Pexels)</label><input name="hero_image_query" class="ad-input" dir="ltr" value="{{ old('hero_image_query', $page->hero_image_query) }}"></div>
            <div><label class="ad-label">جملة البوابة (القسم الانتقالي)</label><input name="content[portal_line]" class="ad-input" value="{{ $get('portal_line') }}"></div>
        </section>

        <section class="ad-card space-y-3 p-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">الاختبار وفورم البيانات</h2>
            <div><label class="ad-label">مقدمة الاختبار</label><input name="content[quiz_intro]" class="ad-input" value="{{ $get('quiz_intro') }}"></div>
            <div><label class="ad-label">عنوان فورم البيانات</label><input name="content[lead_headline]" class="ad-input" value="{{ $get('lead_headline') }}"></div>
            <div><label class="ad-label">نص فورم البيانات</label><input name="content[lead_text]" class="ad-input" value="{{ $get('lead_text') }}"></div>
            <div><label class="ad-label">نص زر النتيجة</label><input name="content[lead_cta]" class="ad-input" value="{{ $get('lead_cta') }}"></div>
            <div><label class="ad-label">نص الموافقة (Consent)</label><textarea name="content[consent_text]" class="ad-textarea" rows="3">{{ $get('consent_text') }}</textarea></div>
            <div><label class="ad-label">ملاحظة الفوتر</label><input name="content[footer_note]" class="ad-input" value="{{ $get('footer_note') }}"></div>
        </section>

        <section class="ad-card space-y-3 p-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">كروت المميزات</h2>
            @for ($i = 0; $i < 3; $i++)
                <div class="grid grid-cols-6 gap-2">
                    <input name="benefits[{{ $i }}][icon]" class="ad-input col-span-1 text-center" maxlength="8" value="{{ data_get($benefits, "$i.icon") }}" placeholder="⚡">
                    <input name="benefits[{{ $i }}][title]" class="ad-input col-span-2" value="{{ data_get($benefits, "$i.title") }}" placeholder="العنوان">
                    <input name="benefits[{{ $i }}][text]" class="ad-input col-span-3" value="{{ data_get($benefits, "$i.text") }}" placeholder="الوصف">
                </div>
            @endfor
        </section>

        <section class="ad-card space-y-3 p-5">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-400">SEO والفعالية</h2>
            <div><label class="ad-label">SEO title</label><input name="seo_title" class="ad-input" value="{{ old('seo_title', $page->seo_title) }}"></div>
            <div><label class="ad-label">SEO description</label><textarea name="seo_description" class="ad-textarea" rows="3">{{ old('seo_description', $page->seo_description) }}</textarea></div>
            <div>
                <label class="ad-label">الفعالية المرتبطة</label>
                <select name="event_id" class="ad-select">
                    <option value="">بدون</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected(old('event_id', $page->event_id) == $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ad-btn ad-btn-primary w-full">حفظ المحتوى</button>
        </section>
    </form>
</x-layouts.admin>
