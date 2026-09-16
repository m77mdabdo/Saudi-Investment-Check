@php
    $rtl = is_rtl($locale);
    $align = $rtl ? 'right' : 'left';
    $tone = match ($lead->result_key) { 'ready' => '#10b981', 'needs_prep' => '#d9a742', default => '#f43f5e' };
    $l = fn (string $key) => __('emails.labels.'.$key);
@endphp

<x-mail.layout :locale="$locale" :subject-line="$subjectLine" :preheader="$preheader" :accent="$tone"
               :footer-note="__('emails.footer_admin_note')" :contact="[]">

    <h1 class="cm-h1" style="margin:0 0 6px; font-size:23px; line-height:31px; font-weight:800; color:#0f172a;">
        {{ __('emails.admin_lead.title') }}
    </h1>

    <p style="margin:0 0 4px; color:#475569;">{{ __('emails.admin_lead.intro', ['name' => $lead->name, 'company' => $lead->company]) }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 0; width:100%;">
        <tr>
            <td align="center" style="padding:16px; border:1px solid #e2e7f0; border-radius:12px; background-color:#fbfcfe;">
                <span dir="ltr" style="font-size:32px; font-weight:800; color:{{ $tone }};">{{ $lead->score }}<span style="font-size:17px; color:#94a3b8;">/{{ $lead->max_score }}</span></span>
                <span style="display:inline-block; margin-{{ $rtl ? 'right' : 'left' }}:10px; padding:4px 14px; border-radius:999px; background-color:{{ $tone }}1a; color:{{ $tone }}; font-size:13px; font-weight:700;">{{ $lead->classification }}</span>
            </td>
        </tr>
    </table>

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_lead.contact_section')" :rows="[
        $l('name') => e($lead->name),
        $l('company') => e($lead->company),
        $l('whatsapp') => '<a href=\''.$lead->whatsappLink().'\' style=\'color:#b9852c\' dir=\'ltr\'>'.e($lead->whatsapp).'</a>',
        $l('email') => $lead->email ? '<a href=\'mailto:'.e($lead->email).'\' style=\'color:#b9852c\' dir=\'ltr\'>'.e($lead->email).'</a>' : null,
    ]" />

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_lead.assessment_section')" :rows="[
        $l('result') => e($rule?->headline ?? $lead->result_key),
        $l('classification') => e($lead->classification),
        __('emails.customer_result.main_question_label') => e($lead->mainQuestion() ?? '—'),
        $l('date') => '<span dir=\'ltr\'>'.$lead->created_at?->format('d M Y H:i').'</span>',
    ]" />

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_lead.answers_section')" :rows="$answers" />

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_lead.source_section')" :rows="[
        $l('source') => e($lead->qrSource?->name ?? $lead->source ?? 'Direct'),
        $l('event') => e($lead->event?->name ?? '—'),
        $l('device') => e(trim(($lead->device ?? '—').' · '.($lead->browser ?? ''), ' ·')),
        'UTM' => e(collect([$lead->utm_source, $lead->utm_medium, $lead->utm_campaign])->filter()->implode(' / ') ?: '—'),
    ]" />

    <x-mail.button :url="$leadUrl" :label="__('emails.admin_lead.cta')" />
</x-mail.layout>
