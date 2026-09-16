@props(['rows' => [], 'title' => null, 'rtl' => true])

@php
    $align = $rtl ? 'right' : 'left';
    $rows = array_filter($rows, fn ($value) => $value !== null && $value !== '');
@endphp

@if ($rows)
    @if ($title)
        <p style="margin:24px 0 8px; font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8; text-align:{{ $align }};">{{ $title }}</p>
    @endif
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; border:1px solid #e2e7f0; border-radius:10px; overflow:hidden;">
        @foreach ($rows as $label => $value)
            <tr>
                <td width="38%" align="{{ $align }}" style="padding:10px 14px; background-color:#f8fafc; border-bottom:1px solid #eef1f7; font-size:13px; color:#64748b; text-align:{{ $align }};">{{ $label }}</td>
                <td align="{{ $align }}" style="padding:10px 14px; border-bottom:1px solid #eef1f7; font-size:14px; font-weight:600; color:#0f172a; text-align:{{ $align }};">{!! $value !!}</td>
            </tr>
        @endforeach
    </table>
@endif
