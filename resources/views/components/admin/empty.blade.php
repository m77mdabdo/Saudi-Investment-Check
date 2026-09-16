@props(['title' => 'مفيش بيانات لسه', 'text' => null, 'ctaLabel' => null, 'ctaUrl' => null, 'icon' => '📭'])

<div class="ad-empty">
    <div class="mb-1 grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-3xl">{{ $icon }}</div>
    <p class="text-base font-extrabold text-slate-700">{{ $title }}</p>
    @if ($text)<p class="max-w-sm text-sm text-slate-500">{{ $text }}</p>@endif
    @if ($ctaLabel && $ctaUrl)
        <a href="{{ $ctaUrl }}" class="ad-btn ad-btn-dark mt-2">{{ $ctaLabel }}</a>
    @endif
    {{ $slot }}
</div>
