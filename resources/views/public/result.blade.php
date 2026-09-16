@php
    $key = $lead->result_key ?? 'early';
    $tone = match ($key) { 'ready' => '#34d399', 'needs_prep' => '#fbbf24', default => '#fb7185' };
    $percent = $lead->scorePercent();
    $circumference = 2 * M_PI * 54;
    $dash = $circumference * ($percent / 100);

    $primaryUrl = $rule?->primary_cta_url
        ?: ($key === 'early' ? ($cta['checklist_url'] ?: $cta['booking_url']) : $cta['booking_url']);
    $secondaryUrl = $rule?->secondary_cta_url ?: $cta['whatsapp_url'];

    $primaryLabel = $rule?->t('primary_cta_label') ?: __('results.primary_cta_fallback');
    $secondaryLabel = $rule?->t('secondary_cta_label') ?: __('results.secondary_cta_fallback');
    $bullets = $rule?->tArray('bullets') ?? [];
@endphp

<x-layouts.public :seo-title="$seoTitle" :seo-description="$seoDescription" robots="noindex,nofollow" :footer-note="$footerNote">
    <div class="mx-auto w-full max-w-2xl px-4 pb-16 pt-4 sm:px-5">

        {{-- ───────── Score reveal ───────── --}}
        <section class="relative overflow-hidden rounded-3xl border border-white/10 sm:rounded-[2rem]">
            <img src="{{ $image['url'] }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-ink-950/88 via-ink-950/82 to-ink-950/96"></div>
            <div class="absolute inset-0" style="background:radial-gradient(28rem 20rem at 50% 0%, {{ $tone }}22, transparent 70%)"></div>

            <div class="relative px-5 py-9 text-center sm:px-10 sm:py-10">
                <p class="text-sm font-bold text-cream/70">{{ $lead->company }}</p>

                <div class="cm-pop relative mx-auto mt-6 grid h-36 w-36 place-items-center sm:h-40 sm:w-40">
                    <svg viewBox="0 0 120 120" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="10"></circle>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $tone }}" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ round($dash, 1) }} {{ round($circumference, 1) }}">
                            <animate attributeName="stroke-dasharray" from="0 {{ round($circumference, 1) }}"
                                     to="{{ round($dash, 1) }} {{ round($circumference, 1) }}" dur="0.9s" fill="freeze"></animate>
                        </circle>
                    </svg>
                    <div>
                        <div class="text-4xl font-black leading-none" dir="ltr">{{ $lead->score }}<span class="text-lg text-cream/50">/{{ $lead->max_score }}</span></div>
                        <div class="mt-1 text-xs font-bold" style="color: {{ $tone }}">{{ $lead->classification }}</div>
                    </div>
                    <span class="sr-only">{{ __('results.score_sr', ['score' => $lead->score, 'max' => $lead->max_score]) }}</span>
                </div>

                <h1 class="cm-fade-up cm-delay-1 cm-h3 mt-7 font-black leading-snug">
                    {{ $rule?->t('headline') ?? __('results.title') }}
                </h1>

                @if ($rule?->t('main_text'))
                    <p class="cm-fade-up cm-delay-2 mx-auto mt-3 max-w-lg text-base leading-relaxed text-cream/80">{{ $rule->t('main_text') }}</p>
                @endif
            </div>
        </section>

        {{-- ───────── What it means ───────── --}}
        <section class="cm-card cm-fade-up cm-delay-2 mt-4 p-5 sm:p-7">
            @if ($rule?->t('body'))
                <div class="space-y-2 text-[0.98rem] leading-relaxed text-cream/85">
                    @foreach (preg_split('/\r?\n/', (string) $rule->t('body')) as $line)
                        @if (trim($line) !== '')<p>{{ $line }}</p>@endif
                    @endforeach
                </div>
            @endif

            @if ($bullets)
                <ul class="mt-5 space-y-2.5">
                    @foreach ($bullets as $bullet)
                        <li class="flex items-start gap-2.5 text-[0.95rem]">
                            <span class="mt-1.5 inline-block h-2 w-2 flex-none rounded-full" style="background: {{ $tone }}"></span>
                            <span class="text-cream/85">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($rule?->t('highlight'))
                <p class="mt-6 rounded-2xl border p-4 text-sm font-bold leading-relaxed"
                   style="border-color: {{ $tone }}55; background: {{ $tone }}14; color: #f6f4f0">
                    {{ $rule->t('highlight') }}
                </p>
            @endif
        </section>

        {{-- ───────── Their main question ───────── --}}
        @if ($lead->mainQuestion())
            <section class="cm-card mt-4 flex items-center gap-4 p-4 sm:p-5">
                <span class="grid h-12 w-12 flex-none place-items-center rounded-2xl bg-gold-400/15 text-2xl">❓</span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-cream/55">{{ __('results.main_question_label') }}</p>
                    <p class="text-base font-extrabold text-gold-200">{{ $lead->mainQuestion() }}</p>
                </div>
            </section>
        @endif

        {{-- ───────── Next step ───────── --}}
        <section class="mt-5 space-y-3">
            @if ($primaryUrl)
                <a href="{{ $primaryUrl }}" target="_blank" rel="noopener" class="cm-btn cm-btn-primary w-full text-center"
                   onclick="cmTrack('meeting_clicked', 'result_primary', '{{ $lead->uuid }}')">
                    {{ $primaryLabel }}
                </a>
            @else
                <div class="cm-card p-5 text-center">
                    <p class="text-base font-extrabold">{{ $primaryLabel }}</p>
                    <p class="mt-1 text-sm text-cream/65">{{ __('results.booth_fallback') }}</p>
                </div>
            @endif

            @if ($secondaryUrl)
                <a href="{{ $secondaryUrl }}" target="_blank" rel="noopener" class="cm-btn cm-btn-ghost w-full text-center"
                   onclick="cmTrack('cta_clicked', 'result_secondary', '{{ $lead->uuid }}')">
                    {{ $secondaryLabel }}
                </a>
            @else
                <p class="text-center text-sm text-cream/65">{{ $secondaryLabel }}</p>
            @endif

            @if ($cta['phone'])
                <a href="tel:{{ preg_replace('/\s+/', '', $cta['phone']) }}" class="block text-center text-sm font-bold text-gold-200"
                   onclick="cmTrack('cta_clicked','result_phone','{{ $lead->uuid }}')">
                    {{ __('results.call_us', ['phone' => $cta['phone']]) }}
                </a>
            @endif
        </section>

        {{-- ───────── Answers recap ───────── --}}
        <details class="cm-card mt-5 p-4 sm:p-5">
            <summary class="cursor-pointer text-sm font-bold text-cream/80">{{ __('results.show_answers') }}</summary>
            <dl class="mt-4 space-y-3">
                @foreach ($lead->answers as $answer)
                    <div class="flex flex-wrap items-start justify-between gap-2 border-b border-white/8 pb-2 last:border-0 sm:flex-nowrap sm:gap-4">
                        <dt class="text-sm text-cream/60">{{ $answer->question?->t('title') ?? $answer->question_title }}</dt>
                        <dd class="text-sm font-bold text-cream">{{ $answer->localizedAnswer() }}</dd>
                    </div>
                @endforeach
            </dl>
        </details>

        <p class="mt-6 text-center text-xs leading-relaxed text-muted">{{ $disclaimer }}</p>

        <p class="mt-4 text-center">
            <a href="{{ lroute('landing') }}" class="text-sm font-bold text-cream/60 underline underline-offset-4">{{ __('results.restart') }}</a>
        </p>
    </div>
</x-layouts.public>
