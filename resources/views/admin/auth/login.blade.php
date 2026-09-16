<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>دخول الفريق — Creative Mark</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css'])
</head>
<body class="min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Visual side --}}
        <div class="relative hidden lg:block">
            <img src="{{ $backdrop['url'] }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-[#05080f]/95 via-[#05080f]/80 to-[#05080f]/60"></div>
            <div class="absolute inset-x-0 bottom-0 p-10">
                <x-brand size="md" />
                <h2 class="mt-6 max-w-sm text-3xl font-black leading-snug text-white">Saudi Market Readiness Console</h2>
                <p class="mt-2 max-w-sm text-sm text-slate-300">تابع الـleads، الحالة، والمصادر لحظة بلحظة أثناء الفعالية.</p>
            </div>
        </div>

        {{-- Form side --}}
        <div class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-sm">
                <div class="lg:hidden"><x-brand size="sm" variant="dark" /></div>

                <h1 class="mt-6 text-2xl font-black text-slate-900">تسجيل دخول الفريق</h1>
                <p class="mt-1 text-sm text-slate-500">الدخول متاح لفريق Creative Mark فقط.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>• {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="ad-label" for="email">البريد الإلكتروني</label>
                        <input id="email" name="email" type="email" class="ad-input" required autofocus autocomplete="username" value="{{ old('email') }}">
                    </div>
                    <div>
                        <label class="ad-label" for="password">كلمة المرور</label>
                        <input id="password" name="password" type="password" class="ad-input" required autocomplete="current-password">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="accent-[#d9a742]"> خليني مسجل دخول
                    </label>
                    <button type="submit" class="ad-btn ad-btn-dark w-full">دخول</button>
                </form>

                @if ($googleEnabled)
                    <div class="my-5 flex items-center gap-3 text-xs text-slate-400">
                        <span class="h-px flex-1 bg-line"></span> أو <span class="h-px flex-1 bg-line"></span>
                    </div>
                    <a href="{{ route('admin.google.redirect') }}" class="ad-btn ad-btn-ghost w-full">
                        <span class="text-base">G</span> الدخول عبر Google
                    </a>
                @endif

                <p class="mt-8 text-center text-xs text-slate-400">
                    <a href="{{ route('landing') }}" class="hover:text-slate-600">← رجوع للصفحة العامة</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
