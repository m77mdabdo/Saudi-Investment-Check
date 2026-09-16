@props(['variant' => 'light', 'size' => 'md'])

@php
    $logo = config('creativemark.brand.logo');
    $hasLogo = $logo && file_exists(public_path($logo));
    $dimensions = match ($size) {
        'sm' => 'h-9',
        'lg' => 'h-16',
        default => 'h-12',
    };
    $text = $variant === 'dark' ? 'text-slate-900' : 'text-white';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    @if ($hasLogo)
        <img src="{{ asset($logo) }}" alt="{{ config('creativemark.brand.name') }}" class="{{ $dimensions }} w-auto rounded-full" />
    @else
        {{-- Typographic mark used until the supplied logo file is dropped into public/images. --}}
        <span class="grid place-items-center rounded-full bg-black {{ $dimensions }} aspect-square">
            <span class="text-[0.95rem] font-extrabold leading-none" style="color:#e9c77b">CM</span>
        </span>
    @endif
    <span class="leading-tight {{ $text }}">
        <span class="block text-[0.95rem] font-extrabold tracking-tight" style="color:#e9c77b">{{ config('creativemark.brand.name') }}</span>
        <span class="block text-[0.68rem] font-medium opacity-70">{{ config('creativemark.brand.tagline') }}</span>
    </span>
</span>
