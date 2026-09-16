@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if ($breadcrumbs)
            <nav class="mb-1 flex items-center gap-1.5 text-xs text-slate-500" aria-label="مسار التنقل">
                @foreach ($breadcrumbs as $label => $url)
                    @if ($url)
                        <a href="{{ $url }}" class="hover:text-slate-800">{{ $label }}</a>
                        <span aria-hidden="true">/</span>
                    @else
                        <span class="text-slate-700">{{ $label }}</span>
                    @endif
                @endforeach
            </nav>
        @endif
        <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
