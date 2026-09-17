<?php

namespace Tests\Feature;

use App\Mail\LeadResultMail;
use App\Mail\LeadStatusMail;
use App\Mail\NewLeadMail;
use App\Mail\TestMail;
use App\Models\NotificationLog;
use App\Models\SalesStatus;
use App\Models\Setting;
use App\Services\MailHealth;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        $this->seed(\Database\Seeders\EnglishContentSeeder::class);
    }

    public function test_both_lead_emails_are_sent_and_tracked(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(NewLeadMail::class, fn (NewLeadMail $mail) => $mail->hasTo(config('mail.from.address')));
        Mail::assertSent(LeadResultMail::class, fn (LeadResultMail $mail) => $mail->hasTo($lead->email));

        $log = NotificationLog::query()->where('template_key', 'customer_result')->firstOrFail();

        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertSame('mail', $log->channel);
        $this->assertSame(config('mail.default'), $log->mailer);
    }

    public function test_the_customer_email_is_written_in_the_language_of_the_lead(): void
    {
        Mail::fake();

        $this->get('/en/quiz');
        $this->submitQuiz(['locale' => 'en']);

        Mail::assertSent(LeadResultMail::class, function (LeadResultMail $mail) {
            $rendered = $this->renderMail($mail);

            return str_contains($rendered, 'Your result is ready')
                && str_contains($rendered, 'dir="ltr"');
        });
    }

    public function test_the_arabic_customer_email_renders_rtl(): void
    {
        Mail::fake();

        $this->submitQuiz(['locale' => 'ar']);

        Mail::assertSent(LeadResultMail::class, function (LeadResultMail $mail) {
            $rendered = $this->renderMail($mail);

            return str_contains($rendered, 'dir="rtl"')
                && str_contains($rendered, 'نتيجة تقييمك جاهزة');
        });
    }

    public function test_the_admin_email_carries_the_full_lead_picture(): void
    {
        Mail::fake();

        $this->get('/?source=booth_qr');
        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(NewLeadMail::class, function (NewLeadMail $mail) use ($lead) {
            $html = $this->renderMail($mail);

            return str_contains($html, $lead->name)
                && str_contains($html, $lead->company)
                && str_contains($html, $lead->whatsapp)
                && str_contains($html, $lead->email)
                && str_contains($html, 'Booth QR')
                && str_contains($html, (string) $lead->score)
                && str_contains($html, route('admin.leads.show', $lead));
        });
    }

    public function test_a_failing_mailer_never_breaks_the_visitor_flow(): void
    {
        // Simulate an unreachable SMTP server.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 2, 'mail.mailers.smtp.timeout' => 1]);

        $response = $this->submitQuiz();

        $lead = $this->latestLead();
        $response->assertRedirect(route('result', ['lead' => $lead->uuid]));

        $log = NotificationLog::query()->where('lead_id', $lead->id)->where('status', 'failed')->first();

        $this->assertNotNull($log, 'The failure should be recorded.');
        $this->assertNotNull($log->failed_at);
        $this->assertNotEmpty($log->error);
    }

    public function test_notifications_can_be_switched_off(): void
    {
        Mail::fake();

        Setting::query()->where('key', 'notify_admin')->update(['value' => '0']);
        Setting::query()->where('key', 'notify_customer')->update(['value' => '0']);
        app(\App\Services\SettingsService::class)->flush();

        $this->submitQuiz();

        Mail::assertNothingSent();
    }

    public function test_a_client_facing_status_emails_the_lead(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();
        $this->flushSession();

        $this->actingAs($this->admin());
        $this->patch('/admin/leads/'.$lead->id.'/status', ['sales_status' => 'meeting'])->assertRedirect();

        Mail::assertSent(LeadStatusMail::class, fn (LeadStatusMail $mail) => $mail->hasTo($lead->email));
        $this->assertDatabaseHas('notification_logs', ['lead_id' => $lead->id, 'template_key' => 'lead_status_update', 'status' => 'sent']);
    }

    public function test_a_non_client_facing_status_sends_nothing(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();
        $this->flushSession();

        $this->actingAs($this->admin());
        $this->patch('/admin/leads/'.$lead->id.'/status', ['sales_status' => 'lost'])->assertRedirect();

        Mail::assertNotSent(LeadStatusMail::class);
    }

    public function test_the_test_email_can_be_sent_from_the_console(): void
    {
        Mail::fake();

        $this->artisan('email:test', ['email' => 'qa@example.com', '--locale' => 'en'])
            ->assertSuccessful();

        Mail::assertSent(TestMail::class, fn (TestMail $mail) => $mail->hasTo('qa@example.com'));
        $this->assertDatabaseHas('notification_logs', ['template_key' => 'test', 'recipient' => 'qa@example.com', 'status' => 'sent']);
    }

    public function test_the_console_rejects_a_malformed_address(): void
    {
        Mail::fake();

        $this->artisan('email:test', ['email' => 'not-an-email'])->assertFailed();
        Mail::assertNothingSent();
    }

    public function test_the_diagnostic_reports_a_log_mailer(): void
    {
        config(['mail.default' => 'log']);

        $report = app(MailHealth::class)->report();

        $this->assertFalse($report['ok']);
        $this->assertNotEmpty(array_filter($report['problems'], fn ($p) => str_contains($p, 'log')));
    }

    public function test_the_diagnostic_passes_with_a_sane_configuration(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.username' => 'user',
            'mail.mailers.smtp.password' => 'secret',
        ]);

        $report = app(MailHealth::class)->report();

        // 127.0.0.1 is flagged, which proves the host check works.
        $this->assertNotEmpty($report['problems']);
        $this->assertArrayHasKey('MAIL_MAILER', $report['config']);
        $this->assertSame('yes', $report['config']['password configured']);
    }

    public function test_no_credential_is_exposed_by_the_diagnostic(): void
    {
        config(['mail.mailers.smtp.password' => 'super-secret-password']);

        $report = app(MailHealth::class)->report();

        $this->assertStringNotContainsString('super-secret-password', json_encode($report));
    }

    public function test_the_admin_can_send_a_test_email_and_read_the_log(): void
    {
        Mail::fake();

        $this->actingAs($this->admin());

        $this->get('/admin/emails')->assertOk()->assertSee('Mail health', false);

        $this->post('/admin/emails/test', ['email' => 'qa@example.com', 'locale' => 'ar'])->assertRedirect();

        Mail::assertSent(TestMail::class);
        $this->get('/admin/emails')->assertOk()->assertSee('qa@example.com');
    }

    public function test_a_failed_email_can_be_resent_from_the_admin(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();
        $this->flushSession();

        $log = app(NotificationService::class)->log($lead, 'customer_result', $lead->email, 'subject', 'failed', 'smtp timeout');

        $this->actingAs($this->admin());
        $this->post('/admin/emails/'.$log->id.'/resend')->assertRedirect();

        Mail::assertSent(LeadResultMail::class);
    }

    public function test_sales_can_read_email_logs_but_not_send_mail(): void
    {
        $this->actingAs($this->admin('sales'));

        // Reading is operational; sending to arbitrary addresses is not.
        $this->get('/admin/emails')->assertOk();
        $this->post('/admin/emails/test', ['email' => 'qa@example.com'])->assertForbidden();
    }

    public function test_recipient_addresses_are_masked_in_the_application_log(): void
    {
        Mail::fake();

        \Illuminate\Support\Facades\Log::spy();

        $this->submitQuiz();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => $message === 'mail.sent'
                && isset($context['recipient'])
                && str_contains($context['recipient'], '***'));
    }

    public function test_status_emails_honour_the_global_switch(): void
    {
        Mail::fake();

        Setting::query()->where('key', 'notify_status_change')->update(['value' => '0']);
        app(\App\Services\SettingsService::class)->flush();

        $this->submitQuiz();
        $lead = $this->latestLead();
        $this->flushSession();

        $this->actingAs($this->admin());
        $this->patch('/admin/leads/'.$lead->id.'/status', ['sales_status' => 'meeting'])->assertRedirect();

        Mail::assertNotSent(LeadStatusMail::class);
    }

    public function test_the_smtp_scheme_is_explicit_and_not_left_to_mail_encryption(): void
    {
        // Laravel 12 selects the transport from `scheme`; MAIL_ENCRYPTION alone
        // is ignored, which is what silently broke delivery in production.
        $config = require base_path('config/mail.php');

        $this->assertArrayHasKey('scheme', $config['mailers']['smtp']);
        $this->assertContains($config['mailers']['smtp']['scheme'], ['smtp', 'smtps']);
        $this->assertIsFloat($config['mailers']['smtp']['timeout']);
        $this->assertGreaterThan(0, $config['mailers']['smtp']['timeout']);
    }

    public function test_the_diagnostic_flags_a_scaffolding_mail_configuration(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2525,
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);

        $problems = implode(' | ', app(MailHealth::class)->report()['problems']);

        $this->assertStringContainsString('not a real SMTP server', $problems);
        $this->assertStringContainsString('2525', $problems);
        $this->assertStringContainsString('username or password is missing', $problems);
    }

    public function test_an_edited_subject_takes_effect_on_the_next_send(): void
    {
        Mail::fake();

        // Regression: the template lookup used to be memoised in a `static`
        // array, so an edited subject was served stale for the life of the
        // PHP process (and, in tests, leaked between cases).
        \App\Models\NotificationTemplate::query()
            ->where('key', 'customer_result')
            ->update(['subject' => 'Updated subject for {{company}}']);

        $this->submitQuiz();

        Mail::assertSent(\App\Mail\LeadResultMail::class, fn ($mail) => str_contains($mail->subjectLine, 'Updated subject for XYZ Technologies'));
    }

    public function test_statuses_can_be_flagged_as_client_facing(): void
    {
        $this->actingAs($this->admin());

        $status = SalesStatus::query()->where('key', 'qualified')->firstOrFail();

        $this->put('/admin/settings/statuses/'.$status->id, [
            'label' => 'Qualified',
            'color' => 'emerald',
            'is_active' => '1',
            'notify_client' => '1',
            'label_en' => 'Qualified',
        ])->assertRedirect();

        $this->assertTrue($status->fresh()->notify_client);
    }
}
