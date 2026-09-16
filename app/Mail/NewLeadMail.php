<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailables\Content;

/** Internal alert for the sales team when a lead completes the assessment. */
class NewLeadMail extends BrandedMail
{
    public function __construct(
        public Lead $lead,
        public string $leadUrl,
        ?string $locale = null,
        ?string $subjectLine = null,
    ) {
        parent::__construct($locale);

        $this->subjectLine = $subjectLine ?: __('emails.admin_lead.subject', [
            'classification' => $lead->classification ?: 'Lead',
            'name' => $lead->name,
            'company' => $lead->company,
            'score' => $lead->score,
            'max' => $lead->max_score,
        ], $this->lang);
    }

    public function content(): Content
    {
        $answers = $this->lead->answers
            ->mapWithKeys(fn ($answer) => [
                (string) ($answer->question?->t('title', $this->lang) ?? $answer->question_title) => e($answer->localizedAnswer() ?? '—'),
            ])
            ->all();

        return new Content(view: 'emails.lead.admin', with: [
            'lead' => $this->lead,
            'rule' => $this->lead->rule,
            'answers' => $answers,
            'leadUrl' => $this->leadUrl,
            'locale' => $this->lang,
            'subjectLine' => $this->subjectLine,
            'preheader' => __('emails.admin_lead.preheader', [
                'source' => $this->lead->qrSource?->name ?? $this->lead->source ?? 'Direct',
                'score' => $this->lead->score,
                'max' => $this->lead->max_score,
            ], $this->lang),
        ]);
    }
}
