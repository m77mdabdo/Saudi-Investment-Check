@props([
    'seoTitle' => 'Saudi-Ready Check — Creative Mark',
    'seoDescription' => 'قيّم جاهزية شركتك لدخول السوق السعودي في أقل من دقيقة مع Creative Mark.',
    'robots' => 'index,follow',
    'footerNote' => null,
])

<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#05080f">
    <meta name="robots" content="{{ $robots }}">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cm-shell cm-aurora antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-gold-400 focus:px-4 focus:py-2 focus:text-ink-950">
        تخطَّ إلى المحتوى
    </a>

    <header class="relative z-20 mx-auto flex w-full max-w-5xl items-center justify-between px-5 pt-5 sm:pt-7">
        <a href="{{ route('landing') }}" aria-label="Creative Mark">
            <x-brand size="sm" />
        </a>
        @isset($headerSlot)
            {{ $headerSlot }}
        @endisset
    </header>

    <main id="main" class="relative z-10">
        {{ $slot }}
    </main>

    <footer class="relative z-10 mx-auto w-full max-w-5xl px-5 pb-8 pt-10 text-center text-xs text-muted">
        <p dir="auto"><span dir="ltr">{{ $footerNote ?: '© '.date('Y').' '.config('creativemark.brand.name') }}</span></p>
    </footer>
</body>
</html>
