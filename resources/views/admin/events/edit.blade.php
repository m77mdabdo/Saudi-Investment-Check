@php $isNew = ! $event->exists; @endphp

<x-layouts.admin :title="$isNew ? 'فعالية جديدة' : $event->name">
    <x-admin.page-header :title="$isNew ? 'فعالية جديدة' : 'تعديل الفعالية'"
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Events' => route('admin.events.index'), ($isNew ? 'جديدة' : $event->name) => null]" />

    <form method="POST" action="{{ $isNew ? route('admin.events.store') : route('admin.events.update', $event) }}" enctype="multipart/form-data" class="ad-card max-w-3xl space-y-4 p-5">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="ad-label" for="name">اسم الفعالية</label><input id="name" name="name" class="ad-input" required value="{{ old('name', $event->name) }}"></div>
            <div><label class="ad-label" for="slug">Slug</label><input id="slug" name="slug" class="ad-input font-mono" dir="ltr" value="{{ old('slug', $event->slug) }}" placeholder="techne-alexandria-2026"></div>
            <div><label class="ad-label" for="city">المدينة</label><input id="city" name="city" class="ad-input" value="{{ old('city', $event->city) }}"></div>
            <div><label class="ad-label" for="country">الدولة</label><input id="country" name="country" class="ad-input" value="{{ old('country', $event->country) }}"></div>
            <div><label class="ad-label" for="starts_at">تاريخ البداية</label><input id="starts_at" type="date" name="starts_at" class="ad-input" value="{{ old('starts_at', $event->starts_at?->toDateString()) }}"></div>
            <div><label class="ad-label" for="ends_at">تاريخ النهاية</label><input id="ends_at" type="date" name="ends_at" class="ad-input" value="{{ old('ends_at', $event->ends_at?->toDateString()) }}"></div>
            <div>
                <label class="ad-label" for="status">الحالة</label>
                <select id="status" name="status" class="ad-select">
                    @foreach (['active' => 'نشطة', 'upcoming' => 'قادمة', 'archived' => 'مؤرشفة'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="ad-label" for="logo">شعار / صورة</label><input id="logo" type="file" name="logo" class="ad-input py-1.5" accept="image/*"></div>
        </div>

        <div><label class="ad-label" for="description">الوصف</label><textarea id="description" name="description" class="ad-textarea" rows="3">{{ old('description', $event->description) }}</textarea></div>

        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" class="h-4 w-4 accent-[#d9a742]" @checked(old('is_default', $event->is_default))> الفعالية الافتراضية للـleads الجديدة</label>

        @if ($event->logo_path)
            <img src="{{ asset('storage/'.$event->logo_path) }}" alt="" class="h-16 w-auto rounded-lg border border-line">
        @endif

        @error('name')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
        @error('ends_at')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

        <div class="flex items-center gap-2">
            <button class="ad-btn ad-btn-primary">{{ $isNew ? 'إنشاء' : 'حفظ' }}</button>
            <a href="{{ route('admin.events.index') }}" class="ad-btn ad-btn-ghost">رجوع</a>
        </div>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-4 max-w-3xl" x-data="confirmAction('حذف الفعالية؟')" @submit="confirm($event)">
            @csrf @method('DELETE')
            <button class="ad-btn ad-btn-danger">حذف الفعالية</button>
        </form>
    @endunless
</x-layouts.admin>
