@php
    $rtl = is_rtl($locale);
    $align = $rtl ? 'right' : 'left';
@endphp

<x-mail.layout :locale="$locale" :subject-line="$subjectLine" :preheader="$preheader"
               :footer-note="__('emails.footer_note')" :contact="$contact">

    <h1 class="cm-h1" style="margin:0 0 6px; font-size:23px; line-height:31px; font-weight:800; color:#0f172a;">
        {{ __('emails.status_update.title') }}
    </h1>

    <p style="margin:0 0 16px; color:#475569;">{{ __('emails.greeting', ['name' => $lead->name]) }}</p>
    <p style="margin:0 0 18px;">{{ __('emails.status_update.intro') }}</p>

    <x-mail.data-table :rtl="$rtl" :rows="[
        __('emails.labels.company') => e($lead->company),
        __('emails.status_update.status_label') => '<strong>'.e($statusLabel).'</strong>',
    ]" />

    @if ($ctaUrl && $ctaLabel)
        <x-mail.button :url="$ctaUrl" :label="$ctaLabel" />
    @endif

    <p style="margin:20px 0 0; color:#475569;">{{ __('emails.status_update.closing') }}</p>
</x-mail.layout>
