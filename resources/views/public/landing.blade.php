@php
    // CMS content wins; the lang files provide the default copy in both languages.
    $c = fn (string $key, $default = null) => data_get($content, $key) ?: $default;

    $benefits = data_get($content, 'benefits');

    if (! is_array($benefits) || $benefits === []) {
        $benefits = [
            ['icon' => '⚡', 'title' => __('home.benefit_speed_title'), 'text' => __('home.benefit_speed_text')],
            ['icon' => '🎯', 'title' => __('home.benefit_clarity_title'), 'text' => __('home.benefit_clarity_text')],
            ['icon' => '🤝', 'title' => __('home.benefit_action_title'), 'text' => __('home.benefit_action_text')],
        ];
    }

    $steps = [
        ['1', __('home.step_one_title', ['count' => $questionCount]), __('home.step_one_text')],
        ['2', __('home.step_two_title'), __('home.step_two_text')],
        ['3', __('home.step_three_title'), __('home.step_three_text')],
    ];
@endphp

<x-layouts.public :seo-title="$seoTitle" :seo-description="$seoDescription" :footer-note="$footerNote" :og-image="$hero['url']">
    {{-- ───────────────── Portal transition ─────────────────
         The page opens here: the artwork zooms into the doorway and washes to
         white, revealing the hero underneath. The image is decorative (alt=""), the wash is aria-hidden, and
         the single line of copy stays a normal, readable paragraph — the page's
         only <h1> belongs to the hero, which this reveals. --}}
    <section class="portal" id="portal">
        <div class="portal__stage">
            <div class="portal__media">
                {{-- Hardcoded on purpose for now: asset()/MediaService resolve against
                     APP_URL, which points at production, so the local page asked the
                     live domain for a file that isn't deployed there. Root-relative
                     works on any host. Move back into MediaService later. --}}
                <img src="/images/fallback/home.png" alt="" fetchpriority="high" decoding="async">
            </div>
            <div class="portal__wash" aria-hidden="true"></div>
            <p class="portal__title">{{ $c('portal_line', __('home.portal_line')) }}</p>
        </div>
    </section>

    {{-- ───────────────── Hero ───────────────── --}}
    <section class="relative mx-auto w-full max-w-5xl px-4 pb-6 pt-6 sm:px-5 sm:pt-12">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 sm:rounded-[2rem]">
            <img src="{{ $hero['url'] }}" alt="" aria-hidden="true" fetchpriority="high"
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-ink-950/85 via-ink-950/80 to-ink-950/95"></div>
            <div class="absolute inset-0" style="background:radial-gradient(40rem 26rem at 80% 0%, rgba(217,167,66,.20), transparent 65%)"></div>

            <div class="relative px-5 py-10 sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                <span class="cm-chip cm-fade-up">
                    <span class="inline-block h-1.5 w-1.5 flex-none rounded-full bg-gold-300"></span>
                    <span class="min-w-0">{{ $c('eyebrow', __('home.eyebrow')) }}</span>
                </span>

                <h1 class="cm-fade-up cm-delay-1 cm-h1 mt-5 font-black leading-[1.15] tracking-tight">
                    {{ $c('hero_kicker', __('home.hero_kicker')) }}
                </h1>

                <p class="cm-fade-up cm-delay-2 mt-4 text-lg font-bold text-gold-200 sm:text-xl">
                    {{ $c('hero_lead', __('home.hero_lead')) }}
                </p>

                <p class="cm-fade-up cm-delay-2 cm-h2 mt-2 font-extrabold leading-snug">
                    {{ $c('hero_title', __('home.hero_title')) }}
                </p>

                <div class="cm-fade-up cm-delay-3 mt-5 max-w-xl space-y-1.5 text-base leading-relaxed text-cream/80 sm:text-lg">
                    @foreach (preg_split('/\r?\n/', (string) $c('hero_description', __('home.hero_description'))) as $line)
                        @if (trim($line) !== '')<p>{{ $line }}</p>@endif
                    @endforeach
                </div>

                <div class="cm-fade-up cm-delay-4 mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ lroute('quiz') }}" class="cm-btn cm-btn-primary w-full sm:w-auto"
                       onclick="window.cmTrack && cmTrack('cta_clicked','start_quiz')">
                        {{ $c('cta_label', __('home.cta_label')) }}
                        <span aria-hidden="true">{{ is_rtl() ? '←' : '→' }}</span>
                    </a>

                    <p class="text-sm font-semibold text-cream/65">
                        {{ $c('hero_meta', __('home.hero_meta', ['count' => $questionCount])) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────── Why bother ───────────────── --}}
    <section class="mx-auto w-full max-w-5xl px-4 py-5 sm:px-5 sm:py-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
    <section class="mx-auto w-full max-w-5xl px-4 py-5 sm:px-5 sm:py-6">
        <div class="cm-card overflow-hidden">
            <div class="grid gap-0 md:grid-cols-[1.15fr_1fr]">
                <div class="p-5 sm:p-8">
                    <h2 class="text-xl font-extrabold sm:text-2xl">{{ __('home.how_title') }}</h2>
                    <ol class="mt-5 space-y-4">
                        @foreach ($steps as $step)
                            @php [$num, $stepTitle, $stepText] = $step; @endphp
                            <li class="flex items-start gap-3">
                                <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-gold-400/15 text-sm font-black text-gold-300">{{ $num }}</span>
                                <span class="min-w-0">
                                    <span class="block font-bold">{{ $stepTitle }}</span>
                                    <span class="block text-sm text-cream/70">{{ $stepText }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <a href="{{ lroute('quiz') }}" class="cm-btn cm-btn-primary mt-7 w-full sm:w-auto"
                       onclick="window.cmTrack && cmTrack('cta_clicked','start_quiz_secondary')">
                        {{ $c('cta_label', __('home.cta_label')) }}
                        <span aria-hidden="true">{{ is_rtl() ? '←' : '→' }}</span>
                    </a>
                </div>

                <div class="relative order-first min-h-44 md:order-last md:min-h-52">
                    <img src="{{ $eventImage['url'] }}" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950/95 via-ink-950/50 to-ink-950/40 md:bg-gradient-to-l md:from-ink-950/30 md:to-ink-950/90"></div>
                    @if ($event)
                        <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6">
                            <p class="text-sm font-bold text-gold-200">{{ $event->t('name') }}</p>
                            <p class="text-xs text-cream/70" dir="ltr">{{ trim($event->city.($event->country ? ', '.$event->country : '')) }} · {{ $event->date_range }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────── Contact strip (only configured links appear) ───────────────── --}}
    @if (array_filter([$cta['whatsapp_url'], $cta['booking_url'], $cta['website']]))
        <section class="mx-auto w-full max-w-5xl px-4 pb-4 pt-2 sm:px-5">
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3">
                @if ($cta['whatsapp_url'])
                    <a href="{{ $cta['whatsapp_url'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm"
                       onclick="cmTrack('cta_clicked','whatsapp_landing')">{{ __('home.contact_whatsapp') }}</a>
                @endif
                @if ($cta['booking_url'])
                    <a href="{{ $cta['booking_url'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm"
                       onclick="cmTrack('meeting_clicked','booking_landing')">{{ __('home.contact_booking') }}</a>
                @endif
                @if ($cta['website'])
                    <a href="{{ $cta['website'] }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost text-sm">{{ __('home.contact_website') }}</a>
                @endif
            </div>
        </section>
    @endif

    <p class="mx-auto max-w-2xl px-6 pb-2 text-center text-xs leading-relaxed text-muted">
        {{ $c('footer_note', __('common.disclaimer')) }}
    </p>
</x-layouts.public>
