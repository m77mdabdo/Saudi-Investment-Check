<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailables\Content;

/** The result summary sent to the person who completed the assessment. */
class LeadResultMail extends BrandedMail
{
    public function __construct(
        public Lead $lead,
        public ?string $resultUrl = null,
        public ?string $ctaUrl = null,
        public ?string $ctaLabel = null,
        public ?string $disclaimer = null,
        ?string $locale = null,
        ?string $subjectLine = null,
    ) {
        parent::__construct($locale ?: $lead->locale);

        $this->subjectLine = $subjectLine ?: __('emails.customer_result.subject', [
            'company' => $lead->company,
        ], $this->lang);
    }

    public function content(): Content
    {
        $rule = $this->lead->rule;

        return new Content(view: 'emails.lead.customer', with: [
            'lead' => $this->lead,
            'rule' => $rule,
            'bullets' => $rule?->tArray('bullets', $this->lang) ?? [],
            'locale' => $this->lang,
            'subjectLine' => $this->subjectLine,
            'preheader' => __('emails.customer_result.preheader', ['company' => $this->lead->company], $this->lang),
            'resultUrl' => $this->resultUrl,
            'ctaUrl' => $this->ctaUrl,
            'ctaLabel' => $this->ctaLabel,
            'disclaimer' => $this->disclaimer,
            'contact' => $this->contactBlock(),
        ]);
    }
}
