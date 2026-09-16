<x-layouts.admin title="Notifications">
    <x-admin.page-header title="Notification center" :subtitle="$unread.' غير مقروء'"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Notifications' => null]">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button class="ad-btn ad-btn-ghost">تعليم الكل كمقروء</button>
            </form>
            @if (auth()->user()->canManagePlatform())
                <a href="{{ route('admin.notifications.templates') }}" class="ad-btn ad-btn-dark">قوالب الإيميل</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="ad-card overflow-hidden">
        @if ($notifications->isEmpty())
            <x-admin.empty icon="🔔" title="مفيش إشعارات" text="أول ما يوصل lead جديد هيظهر هنا فورًا." />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <li class="flex items-center gap-3 px-4 py-3 {{ $notification->isUnread() ? 'bg-amber-50/40' : '' }}">
                        <span class="text-xl">{{ $notification->level === 'success' ? '🔥' : '🔔' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-800">{{ $notification->title }}</p>
                            <p class="truncate text-sm text-slate-500">{{ $notification->body }}</p>
                            <p class="text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if ($notification->lead)
                            <a href="{{ route('admin.notifications.read', $notification) }}" class="ad-btn ad-btn-ghost">فتح الـLead</a>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="border-t border-line px-4 py-3">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
