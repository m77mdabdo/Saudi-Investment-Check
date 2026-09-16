@props(['label', 'value', 'hint' => null, 'tone' => 'slate', 'href' => null, 'icon' => null])

@php
    $tones = [
        'emerald' => 'text-emerald-700 bg-emerald-50',
        'amber' => 'text-amber-700 bg-amber-50',
        'coral' => 'text-rose-700 bg-rose-50',
        'blue' => 'text-blue-700 bg-blue-50',
        'gold' => 'text-[#8a5f10] bg-[#fdf0d5]',
        'slate' => 'text-slate-700 bg-slate-100',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'ad-stat block']) }}>
    <div class="flex items-start justify-between gap-2">
        <span class="text-[0.78rem] font-semibold text-slate-500">{{ $label }}</span>
        @if ($icon)
            <span class="grid h-7 w-7 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['slate'] }}">
                <x-admin.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    <div class="mt-2 text-2xl font-black tracking-tight text-slate-900">{{ $value }}</div>
    @if ($hint)
        <div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>
    @endif
</{{ $tag }}>
