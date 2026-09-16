@props(['color' => 'slate', 'dot' => false])

<span {{ $attributes->merge(['class' => 'ad-badge ad-badge-'.$color]) }}>
    @if ($dot)<span class="inline-block h-1.5 w-1.5 rounded-full bg-current"></span>@endif
    {{ $slot }}
</span>
