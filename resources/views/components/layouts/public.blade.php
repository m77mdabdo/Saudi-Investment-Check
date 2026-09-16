@props([
    'seoTitle' => null,
    'seoDescription' => null,
    'robots' => 'index,follow',
    'footerNote' => null,
    'ogImage' => null,
])

@php
    use App\Support\Locale;

    $locale = app()->getLocale();
    $meta = Locale::meta($locale);
    $alternate = Locale::alternate($locale);
    $title = $seoTitle ?: __('seo.home.title');
    $description = $seoDescription ?: __('seo.home.description');
    $canonical = url()->current();
    $routeName = request()->route()?->getName();
@endphp

<!DOCTYPE html>
<html lang="{{ $meta['html'] }}" dir="{{ $meta['dir'] }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05080f">
    <meta name="robots" content="{{ $robots }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Each language has its own URL, so search engines can index both. --}}
    @if ($routeName)
        @foreach (Locale::supported() as $supported)
            @php $name = Locale::routeName($routeName, $supported); @endphp
            @if (app('router')->has($name))
                <link rel="alternate" hreflang="{{ $supported }}" href="{{ route($name, request()->route()->parameters()) }}">
            @endif
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ route(Locale::routeName($routeName, Locale::default()), request()->route()->parameters()) }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('creativemark.brand.name') }}">
    <meta property="og:locale" content="{{ $meta['iso'] }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cm-shell cm-aurora antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-gold-400 focus:px-4 focus:py-2 focus:text-ink-950">
        {{ __('common.skip_to_content') }}
    </a>

    <header class="relative z-30 mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 pt-4 sm:px-5 sm:pt-6">
        <a href="{{ lroute('landing') }}" aria-label="{{ config('creativemark.brand.name') }}" class="min-w-0">
            <x-brand size="sm" />
        </a>

        <x-language-switcher />
    </header>

    <main id="main" class="relative z-10">
        {{ $slot }}
    </main>

    <footer class="relative z-10 mx-auto w-full max-w-5xl px-4 pb-8 pt-10 text-center text-xs text-muted sm:px-5">
        <p dir="auto"><span dir="ltr">{{ $footerNote ?: __('common.footer_rights', ['year' => date('Y'), 'brand' => config('creativemark.brand.name')]) }}</span></p>
    </footer>
</body>
</html>
