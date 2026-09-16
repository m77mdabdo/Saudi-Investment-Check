<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(protected SettingsService $settings) {}

    /** Fired after a lead is created. Never breaks the visitor's flow. */
    public function leadCreated(Lead $lead): void
    {
        $this->createAdminNotification($lead);

        if ($this->enabled('notify_admin', config('creativemark.notifications.notify_admin'))) {
            $this->sendTemplate('admin_new_lead', $lead, $this->adminRecipients());
        }

        if ($lead->email && $this->enabled('notify_customer', config('creativemark.notifications.notify_customer'))) {
            $this->sendTemplate('customer_result', $lead, [$lead->email]);
        }
    }

    public function enabled(string $key, mixed $default = true): bool
    {
        $value = $this->settings->get($key);

        return $value === null ? (bool) $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /** @return array<int,string> */
    public function adminRecipients(): array
    {
        $raw = (string) $this->settings->get('admin_notification_emails', config('creativemark.notifications.admin_recipients'));

        if (trim($raw) === '') {
            $raw = (string) config('mail.from.address');
        }

        return collect(preg_split('/[,;\s]+/', $raw))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
    }

    /** @param array<int,string> $recipients */
    public function sendTemplate(string $key, Lead $lead, array $recipients): void
    {
        $template = NotificationTemplate::query()->where('key', $key)->where('is_active', true)->first();

        if (! $template || $recipients === []) {
            $this->log($lead, $key, $recipients[0] ?? '—', null, 'skipped', $template ? 'No recipients' : 'Template inactive or missing');

            return;
        }

        $vars = $this->variables($lead);
        $subject = $template->render('subject', $vars);
        $body = $template->render('body', $vars);

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new TemplatedMail(
                    subjectLine: $subject,
                    bodyHtml: $body,
                    ctaLabel: $vars['cta_label'] ?: null,
                    ctaUrl: $vars['cta_url'] ?: null,
                    preheader: Str::limit(strip_tags($body), 120),
                ));

                $this->log($lead, $key, $recipient, $subject, 'sent');
            } catch (\Throwable $e) {
                Log::error('notification.mail_failed', ['template' => $key, 'error' => $e->getMessage()]);
                $this->log($lead, $key, $recipient, $subject, 'failed', Str::limit($e->getMessage(), 480));
            }
        }
    }

    /** @return array<string,string> */
    public function variables(Lead $lead): array
    {
        $rule = $lead->rule;

        return [
            'name' => (string) $lead->name,
            'company' => (string) $lead->company,
            'whatsapp' => (string) $lead->whatsapp,
            'email' => (string) $lead->email,
            'score' => (string) $lead->score,
            'max_score' => (string) $lead->max_score,
            'result' => (string) ($rule?->headline ?? $lead->result_key),
            'classification' => (string) $lead->classification,
            'source' => (string) ($lead->qrSource?->name ?? $lead->source ?? 'Direct'),
            'event' => (string) ($lead->event?->name ?? '—'),
            'main_question' => (string) ($lead->mainQuestion() ?? '—'),
            'cta_url' => (string) ($rule?->primary_cta_url ?: $this->settings->cta('booking_url')),
            'cta_label' => (string) ($rule?->primary_cta_label ?? ''),
            'lead_url' => route('admin.leads.show', $lead),
            'result_url' => URL::temporarySignedRoute('result', now()->addDays((int) config('creativemark.quiz.result_token_ttl_days', 30)), ['lead' => $lead->uuid]),
            'date' => $lead->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i'),
        ];
    }

    public function createAdminNotification(Lead $lead): AdminNotification
    {
        $hot = $lead->result_key === 'ready';

        return AdminNotification::create([
            'type' => 'lead',
            'title' => $hot ? 'Hot Lead جديد 🔥' : 'Lead جديد وصل',
            'body' => trim($lead->name.' — '.$lead->company).' • '.($lead->classification ?: 'Lead').' • '.$lead->score.'/'.$lead->max_score,
            'url' => route('admin.leads.show', $lead),
            'icon' => $hot ? 'flame' : 'user',
            'level' => $hot ? 'success' : 'info',
            'lead_id' => $lead->id,
        ]);
    }

    public function log(?Lead $lead, string $key, string $recipient, ?string $subject, string $status, ?string $error = null): void
    {
        NotificationLog::create([
            'lead_id' => $lead?->id,
            'template_key' => $key,
            'channel' => 'mail',
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => $status,
            'error' => $error,
        ]);
    }

    public function sendPreview(NotificationTemplate $template, string $recipient, ?Lead $lead = null): void
    {
        $vars = $lead ? $this->variables($lead) : $this->sampleVariables();

        Mail::to($recipient)->send(new TemplatedMail(
            subjectLine: '[Preview] '.$template->render('subject', $vars),
            bodyHtml: $template->render('body', $vars),
            ctaLabel: $vars['cta_label'] ?: null,
            ctaUrl: $vars['cta_url'] ?: null,
        ));

        $this->log($lead, $template->key, $recipient, $template->render('subject', $vars), 'sent', 'preview');
    }

    /** @return array<string,string> */
    public function sampleVariables(): array
    {
        return [
            'name' => 'Ahmed Samir',
            'company' => 'XYZ Technologies',
            'whatsapp' => '+201000000000',
            'email' => 'sample@example.com',
            'score' => '10',
            'max_score' => (string) config('creativemark.quiz.max_score', 12),
            'result' => 'Ready',
            'classification' => 'Hot Lead',
            'source' => 'Booth QR',
            'event' => 'TECHNE — Alexandria 2026',
            'main_question' => 'تكلفة التأسيس',
            'cta_url' => $this->settings->cta('booking_url'),
            'cta_label' => 'احجز تقييمك المجاني',
            'lead_url' => route('admin.leads.index'),
            'result_url' => route('landing'),
            'date' => now()->format('d M Y H:i'),
        ];
    }
}
