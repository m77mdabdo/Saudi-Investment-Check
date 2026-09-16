@php
    $rtl = is_rtl($locale);
    $align = $rtl ? 'right' : 'left';
    $tone = match ($lead->result_key) { 'ready' => '#10b981', 'needs_prep' => '#d9a742', default => '#f43f5e' };
@endphp

<x-mail.layout :locale="$locale" :subject-line="$subjectLine" :preheader="$preheader" :accent="$tone"
               :footer-note="__('emails.footer_note')" :contact="$contact">

    <h1 class="cm-h1" style="margin:0 0 6px; font-size:24px; line-height:32px; font-weight:800; color:#0f172a;">
        {{ __('emails.customer_result.title') }}
    </h1>

    <p style="margin:0 0 18px; color:#475569;">{{ __('emails.greeting', ['name' => $lead->name]) }}</p>

    <p style="margin:0 0 20px;">{{ __('emails.customer_result.intro', ['event' => $lead->event?->t('name') ?? config('creativemark.brand.name')]) }}</p>

    {{-- Score card --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="width:100%; border:1px solid #e2e7f0; border-radius:14px; background-color:#fbfcfe;">
        <tr>
            <td align="center" style="padding:22px 18px;">
                <div style="font-size:12px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#94a3b8;">{{ __('emails.customer_result.score_label') }}</div>
                <div class="cm-score" dir="ltr" style="font-size:40px; line-height:48px; font-weight:800; color:{{ $tone }};">{{ $lead->score }}<span style="font-size:20px; color:#94a3b8;">/{{ $lead->max_score }}</span></div>
                <div style="display:inline-block; margin-top:6px; padding:4px 14px; border-radius:999px; background-color:{{ $tone }}1a; color:{{ $tone }}; font-size:13px; font-weight:700;">
                    {{ $lead->classification }}
                </div>
            </td>
        </tr>
    </table>

    @if ($rule?->t('headline'))
        <p style="margin:22px 0 6px; font-size:18px; font-weight:800; color:#0f172a; text-align:{{ $align }};">{{ $rule->t('headline') }}</p>
    @endif

    @if ($rule?->t('main_text'))
        <p style="margin:0 0 14px; color:#475569;">{{ $rule->t('main_text') }}</p>
    @endif

    @if ($bullets)
        <ul style="margin:0 0 6px; padding-{{ $rtl ? 'right' : 'left' }}:20px; color:#334155;">
            @foreach ($bullets as $bullet)
                <li style="margin:0 0 6px;">{{ $bullet }}</li>
            @endforeach
        </ul>
    @endif

    @if ($lead->mainQuestion())
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 0; width:100%; background-color:#fdf7e8; border-radius:12px;">
            <tr>
                <td align="{{ $align }}" style="padding:14px 16px; font-size:14px; color:#7a5a12; text-align:{{ $align }};">
                    <strong>{{ __('emails.customer_result.main_question_label') }}:</strong> {{ $lead->mainQuestion() }}
                </td>
            </tr>
        </table>
    @endif

    @if ($resultUrl)
        <x-mail.button :url="$resultUrl" :label="__('emails.customer_result.cta')" />
    @endif

    @if ($ctaUrl && $ctaLabel)
        <p style="margin:6px 0 0; text-align:{{ $align }};">
            <a href="{{ $ctaUrl }}" style="color:#b9852c; font-weight:700;">{{ $ctaLabel }} →</a>
        </p>
    @endif

    <p style="margin:22px 0 0; color:#475569;">{{ __('emails.customer_result.closing') }}</p>

    @if ($disclaimer)
        <p style="margin:18px 0 0; font-size:12px; line-height:20px; color:#94a3b8;">{{ $disclaimer }}</p>
    @endif
</x-mail.layout>
