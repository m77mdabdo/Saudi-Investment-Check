@props([
    'locale' => null,
    'subjectLine' => null,
    'preheader' => null,
    'accent' => '#d9a742',
    'footerNote' => null,
    'contact' => [],
])

@php
    use App\Support\Locale;

    $locale = $locale ?: app()->getLocale();
    $meta = Locale::meta($locale);
    $rtl = ($meta['dir'] ?? 'rtl') === 'rtl';
    $align = $rtl ? 'right' : 'left';
    $brand = config('creativemark.brand.name');
    $tagline = config('creativemark.brand.tagline');
    $logo = config('creativemark.brand.logo');
    $logoUrl = $logo && file_exists(public_path($logo)) ? asset($logo) : null;
    $font = $rtl ? "'Tajawal','Segoe UI',Tahoma,Arial,sans-serif" : "'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ $meta['html'] ?? 'ar' }}" dir="{{ $meta['dir'] ?? 'rtl' }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>{{ $subjectLine ?: $brand }}</title>
    <!--[if mso]><style type="text/css">body,table,td,a{font-family:Arial,Helvetica,sans-serif !important;}</style><![endif]-->
    <style type="text/css">
        body { margin:0 !important; padding:0 !important; width:100% !important; background-color:#eef1f7; }
        table { border-collapse:collapse !important; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; max-width:100%; }
        a { text-decoration:none; }
        .cm-body { word-break:break-word; }
        @media only screen and (max-width:620px) {
            .cm-wrap { width:100% !important; max-width:100% !important; border-radius:12px !important; }
            .cm-pad { padding-left:18px !important; padding-right:18px !important; }
            .cm-stack { display:block !important; width:100% !important; }
            .cm-h1 { font-size:21px !important; line-height:29px !important; }
            .cm-score { font-size:32px !important; }
            .cm-btn a { display:block !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef1f7;">
    @if ($preheader)
        <div style="display:none; font-size:1px; color:#eef1f7; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">{{ $preheader }}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" class="cm-wrap" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="width:600px; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #e2e7f0;">
                    <tr>
                        <td class="cm-pad" style="background-color:#0a1226; padding:22px 32px;" align="{{ $align }}">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    @if ($logoUrl)
                                        <td width="56" style="padding-{{ $rtl ? 'left' : 'right' }}:12px;" valign="middle">
                                            <img src="{{ $logoUrl }}" width="44" height="44" alt="{{ $brand }}" style="display:block; width:44px; height:44px; border-radius:50%;" />
                                        </td>
                                    @endif
                                    <td valign="middle" align="{{ $align }}">
                                        <div style="font-family:{{ $font }}; font-size:18px; font-weight:800; color:#e9c77b; line-height:24px;">{{ $brand }}</div>
                                        <div style="font-family:{{ $font }}; font-size:12px; color:#93a1bb; line-height:18px;">{{ $tagline }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="height:4px; background-color:{{ $accent }}; line-height:4px; font-size:0;">&nbsp;</td></tr>

                    <tr>
                        <td class="cm-pad cm-body" align="{{ $align }}"
                            style="padding:30px 32px 10px; font-family:{{ $font }}; font-size:15px; line-height:26px; color:#1e293b; text-align:{{ $align }};">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td class="cm-pad" align="{{ $align }}"
                            style="padding:22px 32px 26px; background-color:#f8fafc; border-top:1px solid #e2e7f0; font-family:{{ $font }}; font-size:12px; line-height:20px; color:#64748b; text-align:{{ $align }};">
                            @if ($footerNote)
                                <p style="margin:0 0 8px;">{{ $footerNote }}</p>
                            @endif

                            @if (array_filter([$contact['phone'] ?? null, $contact['email'] ?? null, $contact['website'] ?? null]))
                                <p style="margin:0 0 8px;">
                                    <strong style="color:#334155;">{{ __('emails.contact') }}:</strong>
                                    @if (! empty($contact['phone']))<span dir="ltr">{{ $contact['phone'] }}</span>@endif
                                    @if (! empty($contact['email']))&nbsp;·&nbsp;<a href="mailto:{{ $contact['email'] }}" style="color:#b9852c;">{{ $contact['email'] }}</a>@endif
                                    @if (! empty($contact['website']))&nbsp;·&nbsp;<a href="{{ $contact['website'] }}" style="color:#b9852c;">{{ preg_replace('#^https?://#', '', $contact['website']) }}</a>@endif
                                </p>
                            @endif

                            <p style="margin:0; color:#94a3b8;"><span dir="ltr">&copy; {{ date('Y') }} {{ $brand }}</span></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
