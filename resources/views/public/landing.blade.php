@php
    $c = fn (string $key, $default = null) => data_get($content, $key) ?: $default;
    $benefits = data_get($content, 'benefits') ?: [
        ['icon' => '⚡', 'title' => 'أقل من 60 ثانية', 'text' => 'كله اختيارات — من غير كتابة.'],
        ['icon' => '🎯', 'title' => 'نتيجة واضحة', 'text' => 'تعرف أنت في أي مرحلة بالظبط.'],
        ['icon' => '🤝', 'title' => 'خطوة عملية', 'text' => 'مستشار يقولك تبدأ منين.'],
    ];
@endphp

<x-layouts.public
    :seo-title="$page?->seo_title ?? 'Saudi-Ready Check — Creative Mark'"
    :seo-description="$page?->seo_description"
    :footer-note="$footerNote"
>
    {{-- ───────────────── Hero ───────────────── --}}
    <section class="relative mx-auto w-full max-w-5xl px-5 pb-6 pt-8 sm:pt-14">
        <div class="relative overflow-hidden rounded-[2rem] border border-white/10">
            <img
                src="{{ $hero['url'] }}"
                alt=""
                aria-hidden="true"
                fetchpriority="high"
                class="absolute inset-0 h-full w-full object-cover"
            >
            {{-- Overlay keeps every headline readable over any photo. --}}
            <div class="absolute inset-0 bg-gradient-to-b from-ink-950/85 via-ink-950/80 to-ink-950/95"></div>
            <div class="absolute inset-0" style="background:radial-gradient(40rem 26rem at 80% 0%, rgba(217,167,66,.20), transparent 65%)"></div>

            <div class="relative px-6 py-12 sm:px-12 sm:py-16">
                @if ($event)
                    <span class="cm-chip cm-fade-up">
                        <span class="inline-block h-1.5 w-1.5 flex-none rounded-full bg-gold-300"></span>
                        <span class="min-w-0">{{ $c('eyebrow', 'Creative Mark') }}</span>
                    </span>
                @endif

                <h1 class="cm-fade-up cm-delay-1 mt-6 text-4xl font-black leading-[1.15] tracking-tight sm:text-6xl">
                    {{ $c('hero_kicker', 'بوووم 💥 السعودية مستنياك 🇸🇦') }}
                </h1>

                <p class="cm-fade-up cm-delay-2 mt-4 text-lg font-bold text-gold-200 sm:text-xl">
                    {{ $c('hero_lead', 'بس السؤال الأهم...') }}
                </p>

                <p class="cm-fade-up cm-delay-2 mt-2 text-2xl font-extrabold leading-snug sm:text-4xl">
                    {{ $c('hero_title', 'هل شركتك جاهزة تدخل السوق السعودي؟') }}
                </p>

                <div class="cm-fade-up cm-delay-3 mt-6 max-w-xl space-y-1.5 text-base leading-relaxed text-cream/80 sm:text-lg">
                    @foreach (preg_split('/\r?\n/', (string) $c('hero_description', "جاوب على كام سؤال سريع، وفي أقل من دقيقة هنعرفك:\nجاهز تبدأ؟\nمحتاج تجهيز بسيط؟\nولا محتاج تراجع خطتك الأول؟")) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>

                <div class="cm-fade-up cm-delay-4 mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('quiz') }}"
                       class="cm-btn cm-btn-primary w-full sm:w-auto"
                       onclick="window.cmTrack && cmTrack('cta_clicked','start_quiz')">
                        {{ $c('cta_label', 'ابدأ الرحله') }}
                        <span aria-hidden="true">←</span>
                    </a>

                    <p class="text-sm font-semibold text-cream/65">
                        {{ $c('hero_meta', $questionCount.' أسئلة فقط • أقل من 60 ثانية') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────── Why bother ───────────────── --}}
    <section class="mx-auto w-full max-w-5xl px-5 py-6">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($benefits as $benefit)
                <div class="cm-card p-5">
                    <div class="text-2xl">{{ $benefit['icon'] ?? '✨' }}</div>
                    <h2 class="mt-3 text-base font-extrabold">{{ $benefit['title'] ?? '' }}</h2>
                    <p class="mt-1 text-sm leading-relaxed text-cream/70">{{ $benefit['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ───────────────── How it works ───────────────── --}}
    <section class="mx-auto w-full max-w-5xl px-5 py-6">
        <div class="cm-card overflow-hidden">
            <div class="grid gap-0 md:grid-cols-[1.15fr_1fr]">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-extrabold sm:text-2xl">إزاي بيشتغل؟</h2>
                    <ol class="mt-5 space-y-4">
                        @php
                            $steps = [
                                ['1', 'جاوب على '.$questionCount.' أسئلة', 'كلها اختيارات سريعة — مفيش كتابة.'],
                                ['2', 'سيب بياناتك', 'الاسم، الشركة، والواتساب.'],
                                ['3', 'اعرف نتيجتك فورًا', 'جاهز؟ محتاج تجهيز؟ ولا بدري؟'],
                            ];
                        @endphp
                        @foreach ($steps as $step)
                            @php [$num, $title, $text] = $step; @endphp
                            <li class="flex items-start gap-3">
                                <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-gold-400/15 text-sm font-black text-gold-300">{{ $num }}</span>
                                <span>
                                    <span class="block font-bold">{{ $title }}</span>
                                    <span class="block text-sm text-cream/70">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <a href="{{ route('quiz') }}" class="cm-btn cm-btn-primary mt-7 w-full sm:w-auto"
                       onclick="window.cmTrack && cmTrack('cta_clicked','start_quiz_secondary')">
                        {{ $c('cta_label', 'ابدأ الرحله') }}
                        <span aria-hidden="true">←</span>
                    </a>
                </div>

                <div class="relative min-h-52">
                    <img src="{{ $eventImage['url'] }}" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-l from-ink-950/30 to-ink-950/90"></div>
                    @if ($event)
                        <div class="absolute inset-x-0 bottom-0 p-6">
                            <p class="text-sm font-bold text-gold-200">{{ $event->name }}</p>
                            <p class="text-xs text-cream/70">{{ trim($event->city.($event->country ? '، '.$event->country : '')) }} · {{ $event->date_range }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────── Contact strip (only configured links appear) ───────────────── --}}
    @if (array_filter([$cta['whatsapp_url'], $cta['booking_url'], $cta['website']]))
        <section class="mx-auto w-full max-w-5xl px-5 pb-4 pt-2">
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if ($cta['whatsapp_url'])
                    <a href="{{ $cta['whatsapp_url'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm"
                       onclick="cmTrack('cta_clicked','whatsapp_landing')">تواصل واتساب</a>
                @endif
                @if ($cta['booking_url'])
                    <a href="{{ $cta['booking_url'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm"
                       onclick="cmTrack('meeting_clicked','booking_landing')">احجز اجتماع</a>
                @endif
                @if ($cta['website'])
                    <a href="{{ $cta['website'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm">موقعنا</a>
                @endif
            </div>
        </section>
    @endif

    <p class="mx-auto max-w-2xl px-6 pb-2 text-center text-xs leading-relaxed text-muted">
        {{ $c('footer_note', 'تقييم مبدئي لمستوى الجاهزية — وليس استشارة قانونية أو مالية.') }}
    </p>
</x-layouts.public>
