@php $rtl = is_rtl($locale); @endphp

<x-mail.layout :locale="$locale" :subject-line="$subjectLine" :preheader="__('emails.test.intro', ['app' => config('app.name')])"
               accent="#10b981" :contact="$contact">

    <h1 class="cm-h1" style="margin:0 0 10px; font-size:23px; line-height:31px; font-weight:800; color:#0f172a;">
        {{ __('emails.test.title') }}
    </h1>

    <p style="margin:0 0 18px; color:#475569;">{{ __('emails.test.intro', ['app' => config('app.name')]) }}</p>

    <x-mail.data-table :rtl="$rtl" :rows="$rows" />
</x-mail.layout>
