@php
    $c = fn (string $key, $default = null) => data_get($content, $key) ?: $default;
@endphp

<x-layouts.public seo-title="التقييم — Saudi-Ready Check" robots="noindex,nofollow">
    <div
        x-data="quiz({
            questions: @js($questions),
            saved: @js($saved),
            hasErrors: @js($errors->any()),
            routes: {
                sync: '{{ route('quiz.sync') }}',
                progress: '{{ route('quiz.progress') }}',
                completed: '{{ route('quiz.completed') }}',
                landing: '{{ route('landing') }}',
            },
        })"
        @keydown.window="onKey($event)"
        class="mx-auto w-full max-w-2xl px-5 pb-16 pt-4"
    >
        {{-- ───────── Progress header ───────── --}}
        <div class="sticky top-0 z-20 -mx-5 mb-6 bg-ink-950/85 px-5 pb-4 pt-3 backdrop-blur">
            <div class="mb-2 flex items-center justify-between text-sm font-bold">
                <span class="text-gold-200" x-text="stepLabel"></span>
                <span class="text-cream/55" x-text="`${progress}%`"></span>
            </div>
            <div class="cm-progress" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100" aria-label="تقدّم التقييم">
                <div class="cm-progress-bar" :style="`width:${Math.max(progress, 4)}%`"></div>
            </div>
        </div>

        {{-- ───────── Questions ───────── --}}
        <section x-show="stage === 'questions'" x-cloak>
            <template x-for="(q, qi) in questions" :key="q.key">
                <div x-show="qi === index" class="cm-step-enter" :key="q.key">
                    <div class="mb-6">
                        <div class="mb-3 text-3xl" x-text="q.icon"></div>
                        <h1 class="text-2xl font-black leading-snug sm:text-3xl" x-text="q.title"></h1>
                        <p class="mt-2 text-sm text-cream/65" x-show="q.subtitle" x-text="q.subtitle"></p>
                        @if ($c('quiz_intro'))
                            <p class="mt-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-cream/70" x-show="qi === 0">
                                {{ $c('quiz_intro') }}
                            </p>
                        @endif
                    </div>

                    {{-- Choice questions --}}
                    <div class="space-y-3" x-show="q.type === 'single' || q.type === 'multiple'">
                        <template x-for="(option, oi) in q.options" :key="option.key">
                            <button
                                type="button"
                                class="cm-option"
                                :data-selected="isSelected(q.key, option.key)"
                                :aria-pressed="isSelected(q.key, option.key)"
                                @click="select(q, option)"
                            >
                                <span class="cm-option-icon" x-text="option.icon || '•'"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-base font-bold leading-snug" x-text="option.label"></span>
                                    <span class="mt-0.5 block text-sm text-cream/60" x-show="option.description" x-text="option.description"></span>
                                </span>
                                <span class="cm-option-check" aria-hidden="true">✓</span>
                            </button>
                        </template>

                        {{-- "Other" detail — only appears once that option is chosen --}}
                        <div x-show="needsDetail(q)" x-cloak class="cm-step-enter pt-1">
                            <label class="cm-label" :for="`detail-${q.key}`" x-text="needsDetail(q)?.detail_label || 'اكتب التفاصيل'"></label>
                            <input
                                type="text"
                                class="cm-input"
                                :id="`detail-${q.key}`"
                                maxlength="160"
                                x-model="answers[detailKey(q.key)]"
                                @input.debounce.400ms="sync()"
                                :placeholder="needsDetail(q)?.detail_label || ''"
                            >
                        </div>
                    </div>

                    {{-- Free-text questions --}}
                    <div x-show="q.type === 'text' || q.type === 'textarea'">
                        <template x-if="q.type === 'text'">
                            <input type="text" class="cm-input" maxlength="500" x-model="answers[q.key]" :placeholder="q.placeholder" @input.debounce.400ms="sync()">
                        </template>
                        <template x-if="q.type === 'textarea'">
                            <textarea class="cm-input min-h-32 py-3" maxlength="500" x-model="answers[q.key]" :placeholder="q.placeholder" @input.debounce.400ms="sync()"></textarea>
                        </template>
                    </div>

                    {{-- Navigation --}}
                    <div class="mt-8 flex items-center gap-3">
                        <button type="button" class="cm-btn cm-btn-ghost flex-none px-5" @click="previous()">
                            <span aria-hidden="true">→</span> السابق
                        </button>
                        <button
                            type="button"
                            class="cm-btn cm-btn-primary flex-1"
                            :disabled="!canAdvance()"
                            @click="next()"
                        >
                            <span x-text="index === total - 1 ? 'خلصنا، النتيجة' : 'التالي'"></span>
                            <span aria-hidden="true">←</span>
                        </button>
                    </div>

                    <p class="mt-4 text-center text-xs text-muted" x-show="!canAdvance()">اختار إجابة عشان تكمل 👆</p>
                </div>
            </template>
        </section>

        {{-- ───────── Lead form ───────── --}}
        <section x-show="stage === 'lead'" x-cloak class="cm-step-enter">
            <div class="mb-6">
                <div class="mb-3 text-3xl">👀</div>
                <h1 class="text-2xl font-black leading-snug sm:text-3xl">{{ $c('lead_headline', 'تمام... إحنا تقريبًا عرفنا أنت واقف فين 👀') }}</h1>
                <p class="mt-2 text-sm text-cream/70">{{ $c('lead_text', 'سيب بياناتك ونطلع لك نتيجة الـSaudi-Ready Check.') }}</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-2xl border border-early/40 bg-early/10 p-4" role="alert">
                    <ul class="space-y-1 text-sm text-[#fda4af]">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('quiz.submit') }}" @submit="submit($event)" class="cm-card space-y-4 p-5 sm:p-6" novalidate>
                @csrf

                {{-- Answers travel as hidden fields; scoring always happens server-side. --}}
                <template x-for="field in fields" :key="field.name">
                    <input type="hidden" :name="field.name" :value="field.value">
                </template>

                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div>
                    <label class="cm-label" for="name">الاسم <span class="text-early">*</span></label>
                    <input id="name" name="name" type="text" class="cm-input" required autocomplete="name"
                           value="{{ old('name') }}" placeholder="اسمك بالكامل"
                           @error('name') aria-invalid="true" @enderror>
                    @error('name')<p class="cm-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="cm-label" for="company">اسم الشركة <span class="text-early">*</span></label>
                    <input id="company" name="company" type="text" class="cm-input" required autocomplete="organization"
                           value="{{ old('company') }}" placeholder="اسم شركتك"
                           @error('company') aria-invalid="true" @enderror>
                    @error('company')<p class="cm-error">{{ $message }}</p>@enderror
                </div>

                {{-- Phone with searchable country selector --}}
                <div x-data="phoneField({ countries: @js($countries), defaultIso: @js($defaultCountry), defaultDial: '+966', value: @js(old('country_code')) })">
                    <label class="cm-label" for="phone">رقم الواتساب <span class="text-early">*</span></label>

                    <div class="relative flex gap-2">
                        <button type="button" class="cm-input flex w-auto min-w-28 items-center justify-between gap-2 px-3"
                                @click="toggle()" :aria-expanded="open" aria-haspopup="listbox" aria-label="اختار كود الدولة">
                            <span class="flex items-center gap-1.5">
                                <span class="text-lg" x-text="selected?.flag"></span>
                                <span class="text-sm font-bold" x-text="selected?.dial"></span>
                            </span>
                            <span class="text-xs opacity-60" aria-hidden="true">▾</span>
                        </button>

                        <input type="hidden" name="country_code" :value="selected?.dial">

                        <input id="phone" name="phone" x-ref="phone" type="tel" inputmode="tel" class="cm-input flex-1"
                               required autocomplete="tel" value="{{ old('phone') }}" placeholder="5xxxxxxxx"
                               @error('phone') aria-invalid="true" @enderror>

                        <div x-show="open" x-cloak @click.outside="open = false"
                             class="absolute top-full z-30 mt-2 max-h-72 w-full overflow-hidden rounded-2xl border border-white/12 bg-ink-850 shadow-2xl">
                            <div class="border-b border-white/10 p-2">
                                <input x-ref="search" x-model="search" type="search" class="cm-input h-11 min-h-11 text-sm" placeholder="ابحث عن دولة...">
                            </div>
                            <ul class="max-h-56 overflow-y-auto p-1" role="listbox">
                                <template x-for="country in filtered" :key="country.iso + country.dial">
                                    <li>
                                        <button type="button" class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-start hover:bg-white/8"
                                                @click="choose(country)">
                                            <span class="text-lg" x-text="country.flag"></span>
                                            <span class="flex-1 text-sm" x-text="country.name_ar"></span>
                                            <span class="text-xs text-cream/50" x-text="country.dial"></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="filtered.length === 0" class="px-3 py-4 text-center text-sm text-cream/50">مفيش نتيجة</li>
                            </ul>
                        </div>
                    </div>
                    @error('phone')<p class="cm-error">{{ $message }}</p>@enderror
                    @error('country_code')<p class="cm-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="cm-label" for="email">البريد الإلكتروني <span class="text-cream/40">(اختياري)</span></label>
                    <input id="email" name="email" type="email" class="cm-input" autocomplete="email"
                           value="{{ old('email') }}" placeholder="name@company.com"
                           @error('email') aria-invalid="true" @enderror>
                    @error('email')<p class="cm-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-white/4 p-4">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                           class="mt-0.5 h-5 w-5 flex-none rounded border-white/30 bg-transparent accent-[#e4bd68]">
                    <span class="text-sm leading-relaxed text-cream/80">
                        {{ $c('consent_text', 'أوافق على تواصل فريق Creative Mark معي بخصوص نتيجة التقييم وخيارات دخول السوق السعودي.') }}
                    </span>
                </label>
                @error('consent')<p class="cm-error">{{ $message }}</p>@enderror

                <div class="flex items-center gap-3 pt-1">
                    <button type="button" class="cm-btn cm-btn-ghost flex-none px-5" @click="previous()" :disabled="submitting">
                        <span aria-hidden="true">→</span> السابق
                    </button>
                    <button type="submit" class="cm-btn cm-btn-primary flex-1" :disabled="submitting">
                        <span x-show="!submitting">{{ $c('lead_cta', 'اعرف نتيجتك') }}</span>
                        <span x-show="submitting" x-cloak class="flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-ink-950/30 border-t-ink-950"></span>
                            لحظة بنجهز النتيجة...
                        </span>
                    </button>
                </div>

                <p class="pt-1 text-center text-xs text-muted">بياناتك بتُستخدم للتواصل بخصوص التقييم فقط.</p>
            </form>
        </section>
    </div>

    <style>[x-cloak]{display:none!important}</style>
</x-layouts.public>
