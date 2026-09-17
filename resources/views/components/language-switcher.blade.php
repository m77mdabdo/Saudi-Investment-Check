@php
    use App\Support\Locale;

    $current = app()->getLocale();
    $locales = Locale::all();
@endphp

<nav class="flex flex-none items-center gap-0.5 rounded-full border border-white/12 bg-white/5 p-0.5 text-xs font-bold backdrop-blur"
     aria-label="{{ __('common.language') }}">
    @foreach ($locales as $code => $meta)
        @php $isCurrent = $code === $current; @endphp
        <a
            href="{{ route('language.switch', ['locale' => $code, 'redirect' => Locale::alternateUrl($code)]) }}"
            @class([
                'flex items-center gap-1 rounded-full px-2.5 py-1.5 transition sm:px-3',
                'bg-gold-400 text-ink-950' => $isCurrent,
                'text-cream/70 hover:text-cream' => ! $isCurrent,
            ])
            @if ($isCurrent) aria-current="true" @endif
            hreflang="{{ $code }}"
            lang="{{ $code }}"
            title="{{ __('common.switch_to', ['language' => $meta['native']]) }}"
        >
            <span aria-hidden="true">{{ $meta['flag'] }}</span>
            <span>{{ $meta['native'] }}</span>
        </a>
    @endforeach
</nav>
