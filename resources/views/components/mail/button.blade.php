@props(['url', 'label'])

<table role="presentation" cellpadding="0" cellspacing="0" border="0" class="cm-btn" style="margin:24px 0 6px;">
    <tr>
        <td align="center" bgcolor="#d9a742" style="border-radius:10px;">
            <a href="{{ $url }}" target="_blank" rel="noopener"
               style="display:inline-block; padding:14px 28px; font-size:15px; font-weight:700; color:#241a08; background-color:#d9a742; border-radius:10px; font-family:inherit;">{{ $label }}</a>
        </td>
    </tr>
</table>
