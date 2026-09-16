<x-layouts.admin title="Media">
    <x-admin.page-header title="Media" subtitle="صور الخلفيات بتتخزن في قاعدة البيانات — الصفحة العامة عمرها ما بتنتظر API."
                         :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Media' => null]" />

    @unless ($configured)
        <div class="ad-card mb-5 border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            مفتاح Pexels غير مضبوط في البيئة — الصفحات هتستخدم الصور الاحتياطية المحلية تلقائيًا.
        </div>
    @endunless

    {{-- Current slots --}}
    <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($slots as $key => $label)
            @php $item = $current[$key]; @endphp
            <div class="ad-card overflow-hidden">
                <div class="relative h-28">
                    <img src="{{ $item['url'] }}" alt="" class="h-full w-full object-cover">
                    <span class="absolute top-2 end-2 rounded-full bg-black/60 px-2 py-0.5 text-[0.65rem] font-bold text-white">
                        {{ $item['remote'] ? 'Pexels' : 'Fallback' }}
                    </span>
                </div>
                <div class="p-3">
                    <p class="text-sm font-bold text-slate-800">{{ $label }}</p>
                    <p class="font-mono text-[0.7rem] text-slate-400">{{ $key }}</p>
                    <div class="mt-2 flex flex-wrap gap-1">
                        <a href="{{ route('admin.media.index', ['slot' => $key, 'search' => 1]) }}" class="ad-btn ad-btn-ghost px-2 text-xs">بحث</a>
                        <form method="POST" action="{{ route('admin.media.refresh') }}">
                            @csrf <input type="hidden" name="slot" value="{{ $key }}">
                            <button class="ad-btn ad-btn-ghost px-2 text-xs">تحديث تلقائي</button>
                        </form>
                        @if ($item['remote'])
                            <form method="POST" action="{{ route('admin.media.clear') }}">
                                @csrf <input type="hidden" name="slot" value="{{ $key }}">
                                <button class="ad-btn ad-btn-danger px-2 text-xs">إزالة</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Search --}}
    <section class="ad-card p-5">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="search" value="1">
            <div class="min-w-48 flex-1">
                <label class="ad-label" for="q">كلمات البحث</label>
                <input id="q" name="q" class="ad-input" dir="ltr" value="{{ $query }}" placeholder="riyadh skyline">
            </div>
            <div>
                <label class="ad-label" for="slot">المكان</label>
                <select id="slot" name="slot" class="ad-select w-auto">
                    @foreach ($slots as $key => $label)
                        <option value="{{ $key }}" @selected($slot === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="ad-btn ad-btn-dark">بحث في Pexels</button>
        </form>

        @if ($results)
            <div class="mt-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($results as $photo)
                    <form method="POST" action="{{ route('admin.media.pin') }}" class="ad-card overflow-hidden">
                        @csrf
                        <img src="{{ $photo['thumb_url'] ?? $photo['url'] }}" alt="" loading="lazy" class="h-32 w-full object-cover">
                        <div class="p-2">
                            <p class="truncate text-xs text-slate-500">{{ $photo['photographer'] }}</p>
                            @foreach (['url', 'thumb_url', 'external_id', 'photographer', 'photographer_url', 'avg_color'] as $field)
                                <input type="hidden" name="{{ $field }}" value="{{ $photo[$field] }}">
                            @endforeach
                            <input type="hidden" name="slot" value="{{ $slot }}">
                            <input type="hidden" name="query" value="{{ $query }}">
                            <button class="ad-btn ad-btn-primary mt-2 w-full text-xs">استخدم في {{ $slots[$slot] }}</button>
                        </div>
                    </form>
                @endforeach
            </div>
        @elseif (request()->boolean('search'))
            <x-admin.empty icon="🖼️" title="مفيش نتائج" text="جرب كلمات بحث تانية، أو استخدم الصور الاحتياطية." />
        @endif
    </section>
</x-layouts.admin>
