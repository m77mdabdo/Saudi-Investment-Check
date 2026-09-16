<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
        public ?string $preheader = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.templated', with: [
            'bodyHtml' => $this->bodyHtml,
            'ctaLabel' => $this->ctaLabel,
            'ctaUrl' => $this->ctaUrl,
            'preheader' => $this->preheader,
        ]);
    }
}
