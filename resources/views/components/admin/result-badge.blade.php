@props(['lead'])

@php
    $map = [
        'ready' => ['emerald', 'READY'],
        'needs_prep' => ['amber', 'NEEDS PREP'],
        'early' => ['coral', 'EARLY'],
    ];
    [$color, $label] = $map[$lead->result_key] ?? ['slate', '—'];
@endphp

<x-admin.badge :color="$color" dot>{{ $label }}</x-admin.badge>
