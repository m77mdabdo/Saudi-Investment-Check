@php
    $rtl = is_rtl($locale);
    $l = fn (string $key) => __('emails.labels.'.$key);
@endphp

<x-mail.layout :locale="$locale" :subject-line="$subjectLine" :preheader="$preheader" accent="#d9a742"
               :footer-note="__('emails.footer_admin_note')" :contact="[]">

    <h1 class="cm-h1" style="margin:0 0 6px; font-size:23px; line-height:31px; font-weight:800; color:#0f172a;">
        {{ __('emails.admin_registration.title') }}
    </h1>

    <p style="margin:0 0 4px; color:#475569;">
        {{ __('emails.admin_registration.intro', ['name' => $registration->name]) }}
    </p>

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_registration.contact_section')" :rows="[
        $l('name') => e($registration->name),
        __('emails.labels.whatsapp') => '<a href=\'https://wa.me/'.preg_replace('/\D+/', '', $registration->phone).'\' style=\'color:#b9852c\' dir=\'ltr\'>'.e($registration->phone).'</a>',
        $l('email') => $registration->email ? '<a href=\'mailto:'.e($registration->email).'\' style=\'color:#b9852c\' dir=\'ltr\'>'.e($registration->email).'</a>' : null,
    ]" />

    <x-mail.data-table :rtl="$rtl" :title="__('emails.admin_registration.context_section')" :rows="[
        __('emails.admin_registration.event_label') => e($registration->event?->t('name', $locale) ?? '—'),
        __('emails.admin_registration.photo_label') => $registration->hasPhoto()
            ? __('emails.admin_registration.photo_yes')
            : __('emails.admin_registration.photo_no'),
        __('emails.admin_registration.locale_label') => e(strtoupper((string) $registration->locale)),
        $l('date') => '<span dir=\'ltr\'>'.$registration->created_at?->format('d M Y H:i').'</span>',
    ]" />

    {{-- The photo itself is never attached. It stays on the private disk and is
         only viewable through the authenticated admin route. --}}
    <x-mail.button :url="$registrationUrl" :label="__('emails.admin_registration.cta')" />
</x-mail.layout>
