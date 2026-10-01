@php
    // Stored CMS content WINS over these lang-file defaults: $c() only falls
    // back to __() for a key the admin has not saved. Changing a lang file
    // alone will not change the live page for any key already stored — the
    // stored value must be updated too (see app:refresh-registration-copy).
    $c = fn (string $key, $default = null) => data_get($content, $key) ?: $default;
    $registered = session('registered');
    $photoFailed = (bool) session('photo_failed');

    // Context badge. Both halves come from the active Event record (editable at
    // Admin ▸ Events), so the badge and the registration's event attribution
    // can never disagree. array_filter drops whatever is missing: no event
    // renders nothing, an event with no dates renders the name alone — never a
    // dangling separator. date_range is '' unless a start date exists.
    $eventBadge = $event ? array_filter([$event->t('name'), $event->date_range]) : [];

    $benefits = data_get($content, 'benefits');

    if (! is_array($benefits) || $benefits === []) {
        $benefits = [
            ['icon' => '⚡', 'title' => __('home.benefit_quick_title'), 'text' => __('home.benefit_quick_text')],
            ['icon' => '📞', 'title' => __('home.benefit_followup_title'), 'text' => __('home.benefit_followup_text')],
            ['icon' => '🤝', 'title' => __('home.benefit_topics_title'), 'text' => __('home.benefit_topics_text')],
        ];
    }
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
                @php
                    // The path is configured (config/creativemark.php ▸ media.fallbacks.portal)
                    // but emitted root-relative rather than through asset(): asset() builds
                    // URLs from APP_URL, which points at production, so a local page would
                    // ask the live domain for a file that isn't deployed there. A leading
                    // slash resolves against whatever host is actually serving the page —
                    // including Hostinger, where the root .htaccess rewrites into public/.
                    //
                    // The literal default is a safety net, not a second source of truth: a
                    // server running a config cache built before this key existed gets null
                    // back, and "/{$null}" is "/", which makes the browser fetch the page
                    // itself as an image and draw a broken-image icon. Never emit a bare "/".
                    $portalImage = ltrim(
                        config('creativemark.media.fallbacks.portal') ?: 'images/fallback/home1.webp',
                        '/'
                    );
                @endphp
                <img src="/{{ $portalImage }}"
                     alt="" fetchpriority="high" decoding="async">
            </div>
            <div class="portal__wash" aria-hidden="true"></div>
            <p class="portal__title">{{ $c('portal_line', __('home.portal_line')) }}</p>
            {{-- Deliberately untranslated: "Scroll" stays English in both locales.
                 lang="en" so a screen reader in the Arabic page pronounces it as
                 English rather than sounding it out in Arabic. --}}
            <p class="portal__scroll" lang="en" dir="ltr"><span>Scroll</span></p>
        </div>
    </section>

    {{-- ───────────────── Hero ───────────────── --}}
    <section class="relative mx-auto w-full max-w-5xl px-4 pb-6 pt-6 sm:px-5 sm:pt-12">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 sm:rounded-[2rem]">
            <img src="{{ $hero['url'] }}" alt="" aria-hidden="true" fetchpriority="high"
                 class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-ink-950/85 via-ink-950/80 to-ink-950/95"></div>
            <div class="absolute inset-0" style="background:radial-gradient(40rem 26rem at 80% 0%, rgba(217,167,66,.20), transparent 65%)"></div>

            {{-- Card shell above is unchanged: same rounded container, same hero
                 image treatment, same gradients, same max width. Only the
                 contents are the registration form now. --}}
            <div class="relative px-5 py-10 sm:px-10 sm:py-14 lg:px-14 lg:py-16">
                <div class="mx-auto w-full max-w-xl">
                    @if ($registered)
                        {{-- Confirmation. The journey ends here. --}}
                        <div class="cm-fade-up text-center">
                            <div class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-full bg-gold-400 text-2xl text-ink-950" aria-hidden="true">✓</div>
                            <h1 class="cm-h1 font-black leading-[1.15] tracking-tight">{{ __('registration.success.heading') }}</h1>
                            <p class="mt-4 text-lg text-cream/80">{{ __('registration.success.body', ['name' => $registered]) }}</p>
                            @if ($photoFailed)
                                {{-- The registration still saved; only the photo did not. --}}
                                <p class="mt-3 text-sm text-cream/55">{{ __('registration.success.photo_failed') }}</p>
                            @endif
                            <a href="{{ lroute('landing') }}#register" class="cm-btn cm-btn-ghost mt-7 inline-flex px-5">{{ __('registration.success.again') }}</a>
                        </div>
                    @else
                        @if ($eventBadge)
                            <p class="cm-fade-up mb-3 text-xs font-semibold text-cream/50" dir="auto">{{ implode(' · ', $eventBadge) }}</p>
                        @endif

                        <span class="cm-chip cm-fade-up">
                            <span class="inline-block h-1.5 w-1.5 flex-none rounded-full bg-gold-300"></span>
                            <span class="min-w-0">{{ $c('eyebrow', __('home.eyebrow')) }}</span>
                        </span>

                        <h1 id="register" class="cm-fade-up cm-delay-1 cm-h1 mt-5 font-black leading-[1.15] tracking-tight">
                            {{ $c('welcome_title', __('registration.heading')) }}
                        </h1>

                        <p class="cm-fade-up cm-delay-2 mt-4 text-base leading-relaxed text-cream/80 sm:text-lg">
                            {{ $c('welcome_intro', __('registration.intro')) }}
                        </p>

                        @if ($errors->any())
                            <div class="cm-fade-up mt-6 rounded-2xl border border-early/40 bg-early/10 p-4" role="alert">
                                <p class="mb-1 text-sm font-bold text-[#fda4af]">{{ __('forms.errors_title') }}</p>
                                <ul class="space-y-1 text-sm text-[#fda4af]">
                                    @foreach ($errors->all() as $error)
                                        <li>• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('registration.store') }}" enctype="multipart/form-data"
                              class="cm-fade-up cm-delay-3 mt-7 space-y-5">
                            @csrf

                            {{-- Honeypot --}}
                            <div class="hidden" aria-hidden="true">
                                <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            <div>
                                <label class="cm-label" for="name">{{ __('registration.fields.name') }} <span class="text-early">*</span></label>
                                <input id="name" name="name" type="text" class="cm-input" required autocomplete="name"
                                       value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror>
                                @error('name')<p class="cm-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- Phone with the same searchable country selector the lead form uses --}}
                            <div x-data="phoneField({ countries: @js($countries), defaultIso: @js($defaultCountry), defaultDial: '+966', value: @js(old('country_code')), locale: @js(app()->getLocale()) })">
                                <label class="cm-label" for="phone">{{ __('registration.fields.phone') }} <span class="text-early">*</span></label>

                                <div class="relative flex flex-wrap gap-2">
                                    <button type="button" class="cm-input flex w-auto min-w-24 flex-none items-center justify-between gap-2 px-3"
                                            @click="toggle()" :aria-expanded="open" aria-haspopup="listbox" aria-label="{{ __('registration.fields.country_code') }}">
                                        <span class="flex items-center gap-1.5">
                                            <span class="text-lg" x-text="selected?.flag"></span>
                                            <span class="text-sm font-bold" dir="ltr" x-text="selected?.dial"></span>
                                        </span>
                                        <span class="text-xs opacity-60" aria-hidden="true">▾</span>
                                    </button>

                                    <input type="hidden" name="country_code" :value="selected?.dial">

                                    <input id="phone" name="phone" x-ref="phone" type="tel" inputmode="tel" dir="ltr"
                                           class="cm-input min-w-0 flex-1" required autocomplete="tel" value="{{ old('phone') }}"
                                           @error('phone') aria-invalid="true" @enderror>

                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         class="absolute top-full z-30 mt-2 max-h-72 w-full overflow-hidden rounded-2xl border border-white/12 bg-ink-850 shadow-2xl">
                                        <div class="border-b border-white/10 p-2">
                                            <input x-ref="search" x-model="search" type="search" class="cm-input h-11 min-h-11 text-sm" placeholder="{{ __('quiz.country_search') }}">
                                        </div>
                                        <ul class="max-h-56 overflow-y-auto p-1" role="listbox">
                                            <template x-for="country in filtered" :key="country.iso + country.dial">
                                                <li>
                                                    <button type="button" class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-start hover:bg-white/8"
                                                            @click="choose(country)">
                                                        <span class="text-lg" x-text="country.flag"></span>
                                                        <span class="min-w-0 flex-1 truncate text-sm" x-text="label(country)"></span>
                                                        <span class="flex-none text-xs text-cream/50" dir="ltr" x-text="country.dial"></span>
                                                    </button>
                                                </li>
                                            </template>
                                            <li x-show="filtered.length === 0" class="px-3 py-4 text-center text-sm text-cream/50">{{ __('common.no_results') }}</li>
                                        </ul>
                                    </div>
                                </div>
                                @error('phone')<p class="cm-error">{{ $message }}</p>@enderror
                                @error('country_code')<p class="cm-error">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="cm-label" for="email">{{ __('registration.fields.email') }} <span class="text-cream/40">({{ __('registration.optional') }})</span></label>
                                <input id="email" name="email" type="email" class="cm-input" autocomplete="email" dir="ltr"
                                       value="{{ old('email') }}" @error('email') aria-invalid="true" @enderror>
                                @error('email')<p class="cm-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- accept="image/*" with NO capture attribute, so the OS offers
                                 the camera and the gallery rather than forcing one. --}}
                            <div x-data="photoField({ maxEdge: 1600, messages: { unsupported: @js(__('registration.errors.photo_format')) } })">
                                <label class="cm-label" for="photo">{{ __('registration.fields.photo') }} <span class="text-cream/40">({{ __('registration.optional') }})</span></label>

                                <input id="photo" name="photo" x-ref="input" type="file" accept="image/*"
                                       class="sr-only" @change="pick($event)" @error('photo') aria-invalid="true" @enderror>

                                <div x-show="!preview" class="rounded-2xl border border-dashed border-white/15 bg-white/3 p-5 text-center">
                                    <button type="button" class="cm-btn cm-btn-ghost px-5" @click="$refs.input.click()" :disabled="busy">
                                        <span x-show="!busy">{{ __('registration.photo_choose') }}</span>
                                        <span x-show="busy" x-cloak>{{ __('registration.submitting') }}</span>
                                    </button>
                                    <p class="mt-2 text-xs text-cream/55">{{ __('registration.photo_hint') }}</p>
                                    {{-- The form is complete without this field. --}}
                                    <p class="mt-1 text-xs text-cream/40">{{ __('registration.photo_skip_note') }}</p>
                                </div>

                                <div x-show="preview" x-cloak class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/4 p-3">
                                    <img :src="preview" alt="" class="h-20 w-20 flex-none rounded-xl object-cover">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-cream/85">{{ __('registration.photo_selected') }}</p>
                                        <div class="mt-1.5 flex gap-2">
                                            <button type="button" class="cm-btn cm-btn-ghost h-9 min-h-9 px-3 text-xs" @click="$refs.input.click()">{{ __('registration.photo_replace') }}</button>
                                            <button type="button" class="cm-btn cm-btn-ghost h-9 min-h-9 px-3 text-xs" @click="clear()">{{ __('registration.photo_remove') }}</button>
                                        </div>
                                    </div>
                                </div>

                                <p x-show="error" x-cloak class="cm-error" x-text="error"></p>
                                @error('photo')<p class="cm-error">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit" class="cm-btn cm-btn-primary w-full">{{ __('registration.submit') }}</button>
                        </form>
                    @endif
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
        {{ $c('footer_note', __('registration.privacy_note')) }}
    </p>
</x-layouts.public>
