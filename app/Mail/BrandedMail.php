<?php

namespace App\Mail;

use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Shared chrome for every message the platform sends: brand sender, reply-to,
 * locale handling and the contact block rendered in the footer.
 */
abstract class BrandedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** The language this message is written in (Mailable::$locale is reserved). */
    public string $lang;

    public string $subjectLine = '';

    public function __construct(?string $locale = null)
    {
        $this->lang = $locale ?: app()->getLocale();
        $this->locale($this->lang);
    }

    public function envelope(): Envelope
    {
        $replyTo = (string) app(SettingsService::class)->cta('email');

        return new Envelope(
            subject: $this->subjectLine,
            replyTo: filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? [$replyTo] : [],
        );
    }

    /** Contact details rendered in the footer (only what is configured). */
    protected function contactBlock(): array
    {
        $settings = app(SettingsService::class);

        return array_filter([
            'phone' => $settings->cta('phone'),
            'email' => $settings->cta('email'),
            'website' => $settings->cta('website'),
        ]);
    }
}
