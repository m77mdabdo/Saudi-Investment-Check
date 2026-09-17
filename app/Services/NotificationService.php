<?php

namespace App\Services;

use App\Mail\BrandedMail;
use App\Mail\LeadResultMail;
use App\Mail\LeadStatusMail;
use App\Mail\NewLeadMail;
use App\Mail\TestMail;
use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\SalesStatus;
use App\Support\Locale;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Every outbound message goes through here so that:
 *  - delivery is attempted immediately (no queue worker to depend on),
 *  - a failure is written to notification_logs and the application log,
 *  - a failure never propagates into the visitor's request.
 */
class NotificationService
{
    public function __construct(protected SettingsService $settings) {}

    /* ---------------------------------------------------------------- Leads */

    /**
     * Runs synchronously inside the submit request — no queue, no worker, no cron.
     *
     * Each step is isolated: a dashboard-notification failure must not cancel the
     * emails, and a failing sales email must not cancel the customer's email.
     */
    public function leadCreated(Lead $lead): void
    {
        $this->safely('load_relations', fn () => $lead->loadMissing([
            'answers.question', 'answers.option', 'rule', 'event', 'qrSource',
        ]));

        $this->safely('admin_notification', fn () => $this->createAdminNotification($lead));

        if ($this->enabled('notify_admin', config('creativemark.notifications.notify_admin'))) {
            $this->safely('sales_email', fn () => $this->sendAdminAlert($lead));
        }

        if ($lead->email && $this->enabled('notify_customer', config('creativemark.notifications.notify_customer'))) {
            $this->safely('customer_email', fn () => $this->sendCustomerResult($lead));
        }
    }

    /** Never let one notification step take the others (or the request) down. */
    protected function safely(string $step, callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error('notification.step_failed', [
                'step' => $step,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendCustomerResult(Lead $lead): bool
    {
        if (! $lead->email) {
            return false;
        }

        $locale = $this->leadLocale($lead);
        $rule = $lead->rule;

        $mailable = new LeadResultMail(
            lead: $lead,
            resultUrl: $this->signedResultUrl($lead, $locale),
            ctaUrl: $rule?->primary_cta_url ?: $this->settings->cta('booking_url') ?: null,
            ctaLabel: $rule?->t('primary_cta_label', $locale),
            disclaimer: $rule?->t('disclaimer', $locale) ?: $this->settings->localized('result_disclaimer'),
            locale: $locale,
            subjectLine: $this->templateSubject('customer_result', $lead, $locale),
        );

        return $this->deliver($mailable, $lead->email, $lead, 'customer_result', 'lead', $locale);
    }

    public function sendAdminAlert(Lead $lead): bool
    {
        $recipients = $this->adminRecipients();

        if ($recipients === []) {
            $this->log($lead, 'admin_new_lead', '—', null, 'skipped', 'No admin recipients configured');

            return false;
        }

        $locale = $this->adminLocale();
        $sent = false;

        foreach ($recipients as $recipient) {
            $mailable = new NewLeadMail(
                lead: $lead,
                leadUrl: route('admin.leads.show', $lead),
                locale: $locale,
                subjectLine: $this->templateSubject('admin_new_lead', $lead, $locale),
            );

            $sent = $this->deliver($mailable, $recipient, $lead, 'admin_new_lead', 'lead', $locale) || $sent;
        }

        return $sent;
    }

    /** Fired when a lead reaches a sales status flagged as client-facing. */
    public function leadStatusChanged(Lead $lead, SalesStatus $status): bool
    {
        if (! $status->notify_client || ! $lead->email) {
            return false;
        }

        if (! $this->enabled('notify_status_change', true)) {
            return false;
        }

        $locale = $this->leadLocale($lead);

        $mailable = new LeadStatusMail(
            lead: $lead,
            statusLabel: (string) ($status->t('label', $locale) ?: $status->label),
            ctaUrl: $this->settings->cta('booking_url') ?: $this->settings->cta('whatsapp_url') ?: null,
            ctaLabel: $this->settings->cta('booking_url') ? __('emails.customer_result.cta', [], $locale) : null,
            locale: $locale,
            subjectLine: $this->templateSubject('lead_status_update', $lead, $locale),
        );

        return $this->deliver($mailable, $lead->email, $lead, 'lead_status_update', 'status', $locale);
    }

    /* ----------------------------------------------------------- Diagnostics */

    public function sendTest(string $recipient, ?string $locale = null): bool
    {
        $locale = Locale::isSupported($locale) ? $locale : Locale::default();

        return $this->deliver(new TestMail($locale), $recipient, null, 'test', 'test', $locale, throw: true);
    }

    /** Re-sends a previously logged message. */
    public function resend(NotificationLog $log): bool
    {
        $lead = $log->lead;

        return match ($log->template_key) {
            'customer_result' => $lead ? $this->sendCustomerResult($lead) : false,
            'admin_new_lead' => $lead ? $this->sendAdminAlert($lead) : false,
            'test' => $this->sendTest($log->recipient, $log->locale),
            default => false,
        };
    }

    /* ------------------------------------------------------------- Internals */

    /**
     * Sends one message and records the outcome. Returns false instead of
     * throwing unless the caller explicitly wants the exception (CLI/admin).
     */
    protected function deliver(
        BrandedMail $mailable,
        string $recipient,
        ?Lead $lead,
        string $templateKey,
        string $type,
        string $locale,
        bool $throw = false,
    ): bool {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->log($lead, $templateKey, $recipient, $mailable->subjectLine, 'skipped', 'Invalid recipient address', $type, $locale);

            return false;
        }

        try {
            Mail::to($recipient)->send($mailable);

            $this->log($lead, $templateKey, $recipient, $mailable->subjectLine, 'sent', null, $type, $locale);

            Log::info('mail.sent', [
                'template' => $templateKey,
                'recipient' => $this->maskEmail($recipient),
                'lead_id' => $lead?->id,
                'mailer' => config('mail.default'),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->log($lead, $templateKey, $recipient, $mailable->subjectLine, 'failed', Str::limit($e->getMessage(), 900), $type, $locale);

            Log::error('mail.failed', [
                'template' => $templateKey,
                'recipient' => $this->maskEmail($recipient),
                'lead_id' => $lead?->id,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'error' => $e->getMessage(),
            ]);

            if ($throw) {
                throw $e;
            }

            return false;
        }
    }

    /** Subject override from the editable template, if the admin set one. */
    protected function templateSubject(string $key, Lead $lead, string $locale): ?string
    {
        $template = $this->template($key);

        if (! $template || ! filled($template->subject)) {
            return null;
        }

        $subject = $template->localizedSubject($locale);

        return filled($subject) ? $template->renderString($subject, $this->variables($lead, $locale)) : null;
    }

    /**
     * Editable templates, memoised per instance only.
     *
     * A `static` cache here would survive for the whole PHP process, so an
     * admin editing a subject line (or adding a translation) would keep seeing
     * the old one until the process restarted.
     *
     * @var array<string,NotificationTemplate|null>
     */
    protected array $templates = [];

    protected function template(string $key): ?NotificationTemplate
    {
        if (! array_key_exists($key, $this->templates)) {
            $this->templates[$key] = NotificationTemplate::query()
                ->where('key', $key)
                ->where('is_active', true)
                ->first();
        }

        return $this->templates[$key];
    }

    /**
     * Notification switches, readable under either naming convention so a
     * setting saved as `notify_sales_team` behaves like `notify_admin`.
     *
     * @var array<string,string>
     */
    public const SETTING_ALIASES = [
        'notify_admin' => 'notify_sales_team',
        'notify_status_change' => 'notify_on_status_change',
    ];

    public function enabled(string $key, mixed $default = true): bool
    {
        $value = $this->settings->get($key);

        if ($value === null && isset(self::SETTING_ALIASES[$key])) {
            $value = $this->settings->get(self::SETTING_ALIASES[$key]);
        }

        if ($value === null && ($alias = array_search($key, self::SETTING_ALIASES, true)) !== false) {
            $value = $this->settings->get($alias);
        }

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

    protected function leadLocale(Lead $lead): string
    {
        return Locale::isSupported($lead->locale) ? $lead->locale : Locale::default();
    }

    protected function adminLocale(): string
    {
        $configured = (string) $this->settings->get('admin_email_locale', Locale::default());

        return Locale::isSupported($configured) ? $configured : Locale::default();
    }

    public function signedResultUrl(Lead $lead, ?string $locale = null): string
    {
        $days = (int) config('creativemark.quiz.result_token_ttl_days', 30);
        $name = Locale::routeName('result', $locale ?: $this->leadLocale($lead));

        return URL::temporarySignedRoute($name, now()->addDays($days), ['lead' => $lead->uuid]);
    }

    /** @return array<string,string> */
    public function variables(Lead $lead, ?string $locale = null): array
    {
        $locale ??= $this->leadLocale($lead);
        $rule = $lead->rule;

        return [
            'name' => (string) $lead->name,
            'company' => (string) $lead->company,
            'whatsapp' => (string) $lead->whatsapp,
            'email' => (string) $lead->email,
            'score' => (string) $lead->score,
            'max_score' => (string) $lead->max_score,
            'result' => (string) ($rule?->t('headline', $locale) ?? $lead->result_key),
            'classification' => (string) $lead->classification,
            'source' => (string) ($lead->qrSource?->name ?? $lead->source ?? 'Direct'),
            'event' => (string) ($lead->event?->name ?? '—'),
            'main_question' => (string) ($lead->mainQuestion() ?? '—'),
            'cta_url' => (string) ($rule?->primary_cta_url ?: $this->settings->cta('booking_url')),
            'cta_label' => (string) ($rule?->t('primary_cta_label', $locale) ?? ''),
            'lead_url' => route('admin.leads.show', $lead),
            'result_url' => $this->signedResultUrl($lead, $locale),
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

    public function log(
        ?Lead $lead,
        string $key,
        string $recipient,
        ?string $subject,
        string $status,
        ?string $error = null,
        string $type = 'lead',
        ?string $locale = null,
    ): NotificationLog {
        return NotificationLog::create([
            'lead_id' => $lead?->id,
            'template_key' => $key,
            'type' => $type,
            'channel' => 'mail',
            'locale' => $locale ?: app()->getLocale(),
            'mailer' => config('mail.default'),
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => $status,
            'error' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
        ]);
    }

    /** Never write a full address into the application log. */
    protected function maskEmail(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::limit($user, 2, '').'***@'.$domain;
    }
}
