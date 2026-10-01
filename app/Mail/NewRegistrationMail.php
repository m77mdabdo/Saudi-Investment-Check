<?php

namespace App\Mail;

use App\Models\EventRegistration;
use Illuminate\Mail\Mailables\Content;

/**
 * Internal alert for the sales team when someone registers at an event.
 *
 * Internal only — the person registering is never emailed. The photo is
 * deliberately NOT attached: it stays on the private disk behind the
 * authenticated admin route, and the email carries a link instead.
 */
class NewRegistrationMail extends BrandedMail
{
    public function __construct(
        public EventRegistration $registration,
        public string $registrationUrl,
        ?string $locale = null,
        ?string $subjectLine = null,
    ) {
        parent::__construct($locale);

        $this->subjectLine = $subjectLine ?: __('emails.admin_registration.subject', [
            'name' => $registration->name,
            'event' => $registration->event?->t('name', $this->lang) ?? '—',
        ], $this->lang);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration.admin', with: [
            'registration' => $this->registration,
            'registrationUrl' => $this->registrationUrl,
            'locale' => $this->lang,
            'subjectLine' => $this->subjectLine,
            'preheader' => __('emails.admin_registration.preheader', [
                'name' => $this->registration->name,
                'event' => $this->registration->event?->t('name', $this->lang) ?? '—',
            ], $this->lang),
        ]);
    }
}
