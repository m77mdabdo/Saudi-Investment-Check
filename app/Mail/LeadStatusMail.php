<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailables\Content;

/** Sent to the client when their enquiry reaches a status flagged for notification. */
class LeadStatusMail extends BrandedMail
{
    public function __construct(
        public Lead $lead,
        public string $statusLabel,
        public ?string $ctaUrl = null,
        public ?string $ctaLabel = null,
        ?string $locale = null,
        ?string $subjectLine = null,
    ) {
        parent::__construct($locale ?: $lead->locale);

        $this->subjectLine = $subjectLine ?: __('emails.status_update.subject', [], $this->lang);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.lead.status', with: [
            'lead' => $this->lead,
            'statusLabel' => $this->statusLabel,
            'ctaUrl' => $this->ctaUrl,
            'ctaLabel' => $this->ctaLabel,
            'locale' => $this->lang,
            'subjectLine' => $this->subjectLine,
            'preheader' => __('emails.status_update.preheader', ['status' => $this->statusLabel], $this->lang),
            'contact' => $this->contactBlock(),
        ]);
    }
}
