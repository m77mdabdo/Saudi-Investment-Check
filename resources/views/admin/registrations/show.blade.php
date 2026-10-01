<x-layouts.admin :title="$registration->name">
    <x-admin.page-header :title="$registration->name"
                         :subtitle="$registration->created_at?->format('Y-m-d H:i')"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), __('admin.registrations.title') => route('admin.registrations.index'), $registration->name => null]" />

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="ad-card p-5 lg:col-span-2">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="ad-label">{{ __('registration.fields.name') }}</dt><dd class="font-semibold">{{ $registration->name }}</dd></div>
                <div><dt class="ad-label">{{ __('registration.fields.phone') }}</dt><dd dir="ltr" class="font-semibold">{{ $registration->phone }}</dd></div>
                <div><dt class="ad-label">{{ __('registration.fields.email') }}</dt><dd dir="ltr">{{ $registration->email ?: '—' }}</dd></div>
                <div><dt class="ad-label">{{ __('admin.nav.events') }}</dt><dd>{{ $registration->event?->name ?: '—' }}</dd></div>
                <div><dt class="ad-label">{{ __('admin.nav.qr_sources') }}</dt><dd>{{ $registration->qrSource?->name ?: '—' }}</dd></div>
                <div><dt class="ad-label">Language</dt><dd>{{ $registration->locale }}</dd></div>
                <div><dt class="ad-label">Device</dt><dd>{{ $registration->device ?: '—' }} / {{ $registration->browser ?: '—' }}</dd></div>
                <div><dt class="ad-label">Source</dt><dd>{{ $registration->source ?: '—' }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}" class="mt-6 border-t border-line pt-4"
                  onsubmit="return confirm(@js(__('admin.registrations.confirm_delete')))">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn-danger">{{ __('common.delete') }}</button>
            </form>
        </div>

        <div class="ad-card p-5">
            <p class="ad-label mb-2">{{ __('admin.registrations.photo') }}</p>
            @if ($registration->hasPhoto())
                <img src="{{ route('admin.registrations.photo', $registration) }}" alt=""
                     class="w-full rounded-xl border border-line object-cover">
                <form method="POST" action="{{ route('admin.registrations.photo.destroy', $registration) }}" class="mt-3"
                      onsubmit="return confirm(@js(__('admin.registrations.confirm_photo')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="ad-btn ad-btn-danger w-full">{{ __('admin.registrations.delete_photo') }}</button>
                </form>
            @else
                <div class="ad-empty py-10"><p>{{ __('admin.registrations.no_photo') }}</p></div>
            @endif
        </div>
    </div>
</x-layouts.admin>
