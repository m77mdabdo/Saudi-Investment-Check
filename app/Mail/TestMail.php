<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;

/** Delivery check used by `php artisan email:test` and Admin → Email logs. */
class TestMail extends BrandedMail
{
    public function __construct(?string $locale = null)
    {
        parent::__construct($locale);

        $this->subjectLine = __('emails.test.subject', ['app' => config('app.name')], $this->lang);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test', with: [
            'locale' => $this->lang,
            'subjectLine' => $this->subjectLine,
            'contact' => $this->contactBlock(),
            'rows' => [
                __('emails.test.mailer', [], $this->lang) => config('mail.default'),
                __('emails.test.host', [], $this->lang) => config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'),
                __('emails.labels.email', [], $this->lang) => config('mail.from.address'),
                __('emails.test.sent_at', [], $this->lang) => '<span dir="ltr">'.now()->format('d M Y H:i:s').'</span>',
            ],
        ]);
    }
}
