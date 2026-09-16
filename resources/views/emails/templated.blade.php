<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f8;font-family:'Tajawal',Segoe UI,Arial,sans-serif;color:#0f172a">
    @if (!empty($preheader))
        <span style="display:none;max-height:0;overflow:hidden;opacity:0">{{ $preheader }}</span>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f8;padding:24px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e6e9f2">
                    <tr>
                        <td style="background:#0a1226;padding:22px 26px">
                            <div style="color:#e9c77b;font-size:17px;font-weight:800">{{ config('creativemark.brand.name') }}</div>
                            <div style="color:#9aa8bf;font-size:12px">{{ config('creativemark.brand.tagline') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;font-size:15px;line-height:1.85">
                            {!! $bodyHtml !!}

                            @if (!empty($ctaUrl) && !empty($ctaLabel))
                                <p style="margin:26px 0 6px">
                                    <a href="{{ $ctaUrl }}" style="display:inline-block;background:#d9a742;color:#241a08;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:10px">{{ $ctaLabel }}</a>
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:16px 26px;color:#64748b;font-size:12px;line-height:1.7">
                            {{ config('creativemark.brand.name') }} — Saudi Market Readiness Check.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
