@props(['title' => 'Dashboard'])

@php
    $user = auth()->user();
    $canManage = (bool) $user?->canManagePlatform();

    $nav = [
        __('admin.nav.overview') => [
            ['label' => __('admin.nav.dashboard'), 'route' => 'admin.dashboard', 'icon' => 'dashboard', 'active' => 'admin.dashboard'],
            ['label' => __('admin.nav.analytics'), 'route' => 'admin.analytics', 'icon' => 'analytics', 'active' => 'admin.analytics'],
        ],
        __('admin.nav.sales') => [
            ['label' => __('admin.nav.leads'), 'route' => 'admin.leads.index', 'icon' => 'leads', 'active' => 'admin.leads.*'],
            ['label' => __('admin.nav.notifications'), 'route' => 'admin.notifications.index', 'icon' => 'bell', 'active' => 'admin.notifications.index'],
            ['label' => __('admin.nav.email_logs'), 'route' => 'admin.emails.index', 'icon' => 'mail', 'active' => 'admin.emails.*'],
        ],
        __('admin.nav.experience') => [
            ['label' => __('admin.nav.quiz'), 'route' => 'admin.quiz.index', 'icon' => 'quiz', 'active' => 'admin.quiz.*', 'manage' => true],
            ['label' => __('admin.nav.results'), 'route' => 'admin.results.index', 'icon' => 'results', 'active' => 'admin.results.*', 'manage' => true],
            ['label' => __('admin.nav.cms'), 'route' => 'admin.cms.edit', 'icon' => 'cms', 'active' => 'admin.cms.*', 'manage' => true],
            ['label' => __('admin.nav.media'), 'route' => 'admin.media.index', 'icon' => 'media', 'active' => 'admin.media.*', 'manage' => true],
        ],
        __('admin.nav.setup') => [
            ['label' => __('admin.nav.events'), 'route' => 'admin.events.index', 'icon' => 'events', 'active' => 'admin.events.*', 'manage' => true],
            ['label' => __('admin.nav.qr_sources'), 'route' => 'admin.qr.index', 'icon' => 'qr', 'active' => 'admin.qr.*', 'manage' => true],
            ['label' => __('admin.nav.email_templates'), 'route' => 'admin.notifications.templates', 'icon' => 'mail', 'active' => 'admin.notifications.templates', 'manage' => true],
            ['label' => __('admin.nav.settings'), 'route' => 'admin.settings.edit', 'icon' => 'settings', 'active' => 'admin.settings.*', 'manage' => true],
            ['label' => __('admin.nav.users'), 'route' => 'admin.users.index', 'icon' => 'users', 'active' => 'admin.users.*', 'manage' => true],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::meta()['html'] }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} — {{ __('admin.console') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body x-data="adminShell" class="min-h-screen">
    @if (session('success') || session('error'))
        <script id="flash-data" type="application/json">@json(['type' => session('error') ? 'error' : 'success', 'message' => session('error') ?? session('success')])</script>
    @endif

    <div class="flex min-h-screen">
        {{-- ───────── Sidebar ───────── --}}
        <aside
            class="fixed inset-y-0 z-40 w-[17rem] shrink-0 overflow-y-auto bg-nav-900 px-3 pb-6 pt-5 transition-transform lg:static lg:translate-x-0"
            :class="sidebar ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        >
            <a href="{{ route('admin.dashboard') }}" class="mb-4 flex items-center gap-2 px-2">
                <x-brand size="sm" />
            </a>

            <nav class="space-y-0.5">
                @foreach ($nav as $group => $items)
                    @php $visible = collect($items)->filter(fn ($i) => ! ($i['manage'] ?? false) || $canManage); @endphp
                    @if ($visible->isNotEmpty())
                        <p class="ad-nav-group">{{ $group }}</p>
                        @foreach ($visible as $item)
                            <a href="{{ route($item['route']) }}"
                               class="ad-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"
                               @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                                <x-admin.icon :name="$item['icon']" class="h-[1.15rem] w-[1.15rem] opacity-90" />
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if ($item['route'] === 'admin.notifications.index' && $unreadNotifications)
                                    <span class="rounded-full bg-[#e4bd68] px-1.5 text-[0.7rem] font-black text-[#241a08]">{{ $unreadNotifications }}</span>
                                @endif
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </nav>

            <div class="mt-6 rounded-xl border border-white/10 bg-white/5 p-3">
                <p class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">{{ __('admin.live_event') }}</p>
                <p class="mt-1 text-sm font-extrabold text-white">{{ $currentEvent?->name ?? 'No active event' }}</p>
                @if ($currentEvent?->city)
                    <p class="text-xs text-slate-400" dir="ltr">{{ $currentEvent->city }} · {{ $currentEvent->date_range }}</p>
                @endif
                <a href="{{ route('landing') }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs font-bold text-[#eccf8b] hover:underline">
                    {{ __('admin.open_public_site') }} ↗
                </a>
            </div>
        </aside>

        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

        {{-- ───────── Main ───────── --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-line bg-white/85 backdrop-blur">
                <div class="flex items-center gap-3 px-4 py-3 sm:px-6">
                    <button type="button" class="ad-btn ad-btn-ghost px-2.5 lg:hidden" @click="sidebar = !sidebar" aria-label="{{ __('common.search') }}">☰</button>

                    <form method="GET" action="{{ route('admin.leads.index') }}" class="relative min-w-0 flex-1 max-w-md">
                        <x-admin.icon name="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('admin.search_placeholder') }}"
                               class="ad-input ps-9" aria-label="{{ __('admin.search_placeholder') }}">
                    </form>

                    <div class="ms-auto flex items-center gap-2">
                        <a href="{{ route('admin.leads.index', ['result' => 'ready']) }}" class="ad-btn ad-btn-ghost hidden sm:inline-flex">
                            <x-admin.icon name="flame" class="h-4 w-4 text-rose-500" /> {{ __('admin.hot_leads') }}
                        </a>

                        {{-- Notifications --}}
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" class="ad-btn ad-btn-ghost relative px-2.5" @click="open = !open" aria-label="{{ __('admin.nav.notifications') }}">
                                <x-admin.icon name="bell" class="h-4 w-4" />
                                @if ($unreadNotifications)
                                    <span class="absolute -top-1 -end-1 grid h-4 min-w-4 place-items-center rounded-full bg-rose-500 px-1 text-[0.65rem] font-black text-white">{{ $unreadNotifications }}</span>
                                @endif
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false"
                                 class="absolute end-0 z-30 mt-2 w-80 overflow-hidden rounded-xl border border-line bg-white shadow-xl">
                                <div class="flex items-center justify-between border-b border-line px-3 py-2">
                                    <span class="text-sm font-bold">{{ __('admin.nav.notifications') }}</span>
                                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                                        @csrf
                                        <button class="text-xs font-bold text-slate-500 hover:text-slate-800">{{ __('admin.mark_all_read') }}</button>
                                    </form>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    @forelse ($latestNotifications as $notification)
                                        <a href="{{ route('admin.notifications.read', $notification) }}"
                                           class="flex gap-2 border-b border-slate-50 px-3 py-2.5 hover:bg-slate-50 {{ $notification->isUnread() ? 'bg-amber-50/40' : '' }}">
                                            <span class="mt-0.5 text-lg">{{ $notification->level === 'success' ? '🔥' : '🔔' }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-bold text-slate-800">{{ $notification->title }}</span>
                                                <span class="block truncate text-xs text-slate-500">{{ $notification->body }}</span>
                                                <span class="block text-[0.7rem] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                            </span>
                                        </a>
                                    @empty
                                        <p class="px-3 py-6 text-center text-sm text-slate-500">{{ __('admin.no_notifications') }}</p>
                                    @endforelse
                                </div>
                                <a href="{{ route('admin.notifications.index') }}" class="block border-t border-line px-3 py-2 text-center text-xs font-bold text-slate-600 hover:bg-slate-50">{{ __('admin.view_all') }}</a>
                            </div>
                        </div>

                        {{-- User menu --}}
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" class="flex items-center gap-2 rounded-lg border border-line bg-white px-2 py-1.5" @click="open = !open">
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-nav-900 text-xs font-black text-[#eccf8b]">{{ $user?->initials() }}</span>
                                <span class="hidden text-sm font-bold sm:inline">{{ $user?->name }}</span>
                            </button>
                            <div x-show="open" x-cloak @click.outside="open = false"
                                 class="absolute end-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-white shadow-xl">
                                <div class="border-b border-line px-3 py-2">
                                    <p class="text-sm font-bold">{{ $user?->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
                                    <p class="mt-1 text-[0.7rem] font-bold text-slate-400">{{ $user?->roleLabel() }}</p>
                                </div>
                                <a href="{{ route('admin.leads.export', request()->query()) }}" class="block px-3 py-2 text-sm hover:bg-slate-50">{{ __('admin.export_excel') }}</a>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button class="block w-full px-3 py-2 text-start text-sm text-rose-600 hover:bg-rose-50">{{ __('admin.logout') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="ad-shell-main min-w-0 flex-1 px-3 py-5 sm:px-6 sm:py-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast" x-cloak
         class="fixed bottom-5 start-5 z-50 max-w-sm rounded-xl px-4 py-3 text-sm font-bold text-white shadow-2xl"
         :class="toast?.type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'"
         x-transition.opacity role="status">
        <span x-text="toast?.message"></span>
    </div>

    <style>[x-cloak]{display:none!important}</style>
</body>
</html>
