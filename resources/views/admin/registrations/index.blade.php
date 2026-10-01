<x-layouts.admin :title="__('admin.registrations.title')">
    <x-admin.page-header :title="__('admin.registrations.title')"
                         :subtitle="$registrations->total().' — '.__('admin.registrations.subtitle')"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), __('admin.registrations.title') => null]">
        <x-slot:actions>
            <a href="{{ route('admin.registrations.export', request()->query()) }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="export" class="h-4 w-4" /> {{ __('admin.registrations.export') }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="GET" action="{{ route('admin.registrations.index') }}" class="ad-card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="min-w-48 flex-1">
            <label class="ad-label" for="q">{{ __('admin.registrations.search_placeholder') }}</label>
            <input id="q" name="q" type="search" class="ad-input" value="{{ $filters['q'] }}">
        </div>
        <div>
            <label class="ad-label" for="event_id">{{ __('admin.nav.events') }}</label>
            <select id="event_id" name="event_id" class="ad-select">
                <option value="">{{ __('admin.registrations.all_events') }}</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) $filters['event_id'] === (string) $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="ad-label" for="has_photo">{{ __('admin.registrations.has_photo') }}</label>
            <select id="has_photo" name="has_photo" class="ad-select">
                <option value="">—</option>
                <option value="yes" @selected($filters['has_photo'] === 'yes')>{{ __('admin.registrations.with_photo') }}</option>
                <option value="no" @selected($filters['has_photo'] === 'no')>{{ __('admin.registrations.without_photo') }}</option>
            </select>
        </div>
        <button type="submit" class="ad-btn ad-btn-dark">
            <x-admin.icon name="search" class="h-4 w-4" />
        </button>
    </form>

    <div class="ad-card overflow-hidden">
        @if ($registrations->isEmpty())
            <div class="ad-empty"><p>{{ __('admin.registrations.empty') }}</p></div>
        @else
            <div class="ad-table-wrap"><table class="ad-table">
                <thead>
                    <tr>
                        <th>{{ __('admin.registrations.photo') }}</th>
                        <th>{{ __('registration.fields.name') }}</th>
                        <th>{{ __('registration.fields.phone') }}</th>
                        <th>{{ __('registration.fields.email') }}</th>
                        <th>{{ __('admin.nav.events') }}</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $registration)
                        <tr>
                            <td>
                                @if ($registration->hasPhoto())
                                    {{-- Served through the auth-gated route, never a public path. --}}
                                    <img src="{{ route('admin.registrations.photo', $registration) }}" alt=""
                                         class="h-11 w-11 rounded-lg object-cover" loading="lazy">
                                @else
                                    <span class="ad-badge ad-badge-slate">{{ __('admin.registrations.no_photo') }}</span>
                                @endif
                            </td>
                            <td class="font-semibold">{{ $registration->name }}</td>
                            <td dir="ltr">{{ $registration->phone }}</td>
                            <td dir="ltr">{{ $registration->email ?: '—' }}</td>
                            <td>{{ $registration->event?->name ?: '—' }}</td>
                            <td>{{ $registration->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.registrations.show', $registration) }}" class="ad-btn ad-btn-ghost h-8 min-h-8 px-2.5 text-xs">↗</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>

            <div class="p-4">{{ $registrations->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
