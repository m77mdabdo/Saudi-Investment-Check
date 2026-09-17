<?php

namespace Tests\Feature;

use App\Mail\LeadResultMail;
use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The whole "customer presses Submit" path, end to end.
 *
 * Everything here must happen inside the one request: no queue worker, no cron,
 * no artisan command. These tests pin that contract.
 */
class LeadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        $this->seed(\Database\Seeders\EnglishContentSeeder::class);
    }

    /* ─────────────────────────── 1 & 2. Lead + result ─────────────────────── */

    public function test_submitting_the_quiz_creates_a_lead_with_its_result(): void
    {
        Mail::fake();

        $response = $this->submitQuiz();
        $lead = $this->latestLead();

        $this->assertNotNull($lead, 'The lead must be persisted.');
        $response->assertRedirect(route('result', ['lead' => $lead->uuid]));

        // Contact details
        $this->assertSame('Ahmed Samir', $lead->name);
        $this->assertSame('XYZ Technologies', $lead->company);
        $this->assertSame('+201000000000', $lead->whatsapp);
        $this->assertSame('ahmed@example.com', $lead->email);
        $this->assertTrue($lead->consent);
        $this->assertNotNull($lead->consent_at);

        // Result, computed server-side and stored
        $this->assertSame(12, $lead->score);
        $this->assertSame(12, $lead->max_score);
        $this->assertSame('ready', $lead->result_key);
        $this->assertSame('Hot Lead', $lead->classification);
        $this->assertNotNull($lead->result_rule_id);
        $this->assertSame('ready', $lead->rule->key);

        // Every answer stored, and the summary built
        $this->assertCount(8, $lead->answers);
        $this->assertSame(12, (int) $lead->answers->sum('score'));
        $this->assertNotEmpty($lead->answers_summary);

        // Sales pipeline defaults
        $this->assertSame('new', $lead->sales_status);
        $this->assertNotNull($lead->status_changed_at);
    }

    public function test_the_result_page_is_reachable_right_after_submitting(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        $this->get(route('result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee((string) $lead->score, false)
            ->assertSee($lead->company);
    }

    /* ─────────────────────── 3 & 4. Both emails, automatically ────────────── */

    public function test_both_emails_go_out_automatically_on_submit(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(LeadResultMail::class, fn (LeadResultMail $mail) => $mail->hasTo($lead->email));
        Mail::assertSent(NewLeadMail::class, fn (NewLeadMail $mail) => $mail->hasTo(config('mail.from.address')));
        Mail::assertSentCount(2);
    }

    public function test_the_sales_email_carries_everything_the_team_needs(): void
    {
        Mail::fake();

        $this->get('/?source=booth_qr');
        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(NewLeadMail::class, function (NewLeadMail $mail) use ($lead) {
            $html = $this->renderMail($mail);

            foreach ([
                $lead->name,                                   // name
                $lead->email,                                  // email
                $lead->whatsapp,                               // phone
                'تكنولوجيا وبرمجيات',                            // sector / activity
                (string) $lead->score,                         // result
                $lead->classification,
                $lead->created_at->format('d M Y H:i'),        // submitted at
                'Booth QR',                                    // source
                route('admin.leads.show', $lead),              // admin link
            ] as $needle) {
                if (! str_contains($html, $needle)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_the_customer_email_links_to_their_result(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(LeadResultMail::class, function (LeadResultMail $mail) use ($lead) {
            $html = $this->renderMail($mail);

            return str_contains($html, $lead->uuid)              // signed result link
                && str_contains($html, 'signature=')
                && ! str_contains($html, 'Whoops')               // no debug output
                && ! str_contains($html, 'vendor/laravel');
        });
    }

    /* ───────────────────── 5 & 6. The settings are respected ──────────────── */

    public function test_customer_notification_off_sends_only_the_sales_email(): void
    {
        Mail::fake();
        $this->switchSetting('notify_customer', '0');

        $this->submitQuiz();

        Mail::assertNotSent(LeadResultMail::class);
        Mail::assertSent(NewLeadMail::class);
    }

    public function test_sales_notification_off_sends_only_the_customer_email(): void
    {
        Mail::fake();
        $this->switchSetting('notify_admin', '0');

        $this->submitQuiz();

        Mail::assertNotSent(NewLeadMail::class);
        Mail::assertSent(LeadResultMail::class);
    }

    public function test_the_sales_switch_also_answers_to_its_alias(): void
    {
        Mail::fake();

        // Saved under the alternative name used in the admin copy.
        Setting::query()->updateOrCreate(
            ['key' => 'notify_sales_team'],
            ['value' => '0', 'type' => 'bool', 'group' => 'notifications'],
        );
        Setting::query()->where('key', 'notify_admin')->delete();
        app(SettingsService::class)->flush();

        $this->submitQuiz();

        Mail::assertNotSent(NewLeadMail::class);
    }

    public function test_a_lead_without_an_email_only_notifies_sales(): void
    {
        Mail::fake();

        $this->submitQuiz(['email' => null]);

        Mail::assertNotSent(LeadResultMail::class);
        Mail::assertSent(NewLeadMail::class);
        $this->assertSame(1, NotificationLog::query()->where('status', 'sent')->count());
    }

    /* ──────────────────────── 7, 8, 9. Failure handling ───────────────────── */

    public function test_a_broken_mailer_still_produces_a_lead_and_a_result_page(): void
    {
        $this->breakTheMailer();

        $response = $this->submitQuiz();

        $lead = $this->latestLead();
        $this->assertNotNull($lead, 'The lead must survive an SMTP failure.');
        $this->assertSame(12, $lead->score);

        // No 500 — the visitor is redirected to their result as usual.
        $response->assertStatus(302);
        $response->assertRedirect(route('result', ['lead' => $lead->uuid]));
        $this->get(route('result', ['lead' => $lead->uuid]))->assertOk();
    }

    public function test_a_failed_send_is_recorded_with_its_error(): void
    {
        $this->breakTheMailer();

        $this->submitQuiz();
        $lead = $this->latestLead();

        $failures = NotificationLog::query()->where('lead_id', $lead->id)->where('status', 'failed')->get();

        $this->assertNotEmpty($failures, 'Every failed send must leave a row.');

        foreach ($failures as $log) {
            $this->assertNotNull($log->failed_at);
            $this->assertNull($log->sent_at, 'A failed row must never claim a send time.');
            $this->assertNotEmpty($log->error);
            $this->assertContains($log->template_key, ['customer_result', 'admin_new_lead']);
        }
    }

    public function test_one_failing_step_does_not_cancel_the_others(): void
    {
        Mail::fake();

        // Make the in-dashboard notification step blow up: the emails — which
        // run after it — must still be sent, and the visitor must be unaffected.
        \Illuminate\Support\Facades\Schema::drop('admin_notifications');

        $response = $this->submitQuiz();

        $lead = $this->latestLead();
        $this->assertNotNull($lead);
        $response->assertRedirect(route('result', ['lead' => $lead->uuid]));

        Mail::assertSent(NewLeadMail::class);
        Mail::assertSent(LeadResultMail::class);
    }

    /* ──────────────────────── 10 & 14. Notification logs ──────────────────── */

    public function test_successful_sends_are_logged_with_the_full_context(): void
    {
        Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        $customer = NotificationLog::query()->where('template_key', 'customer_result')->firstOrFail();
        $sales = NotificationLog::query()->where('template_key', 'admin_new_lead')->firstOrFail();

        foreach ([$customer, $sales] as $log) {
            $this->assertSame('sent', $log->status);
            $this->assertNotNull($log->sent_at);
            $this->assertNull($log->failed_at);
            $this->assertNull($log->error);
            $this->assertSame('mail', $log->channel);
            $this->assertSame(config('mail.default'), $log->mailer);
            $this->assertSame($lead->id, $log->lead_id);
            $this->assertNotEmpty($log->subject);
            $this->assertNotEmpty($log->locale);
        }

        $this->assertSame($lead->email, $customer->recipient);
        $this->assertSame('lead', $customer->type);
        $this->assertSame(config('mail.from.address'), $sales->recipient);
        $this->assertStringContainsString($lead->company, $sales->subject);
    }

    /* ─────────────────────────── 11 & 12. Languages ───────────────────────── */

    public function test_an_arabic_submission_produces_arabic_emails(): void
    {
        Mail::fake();

        $this->get('/quiz')->assertOk();
        $this->submitQuiz(['locale' => 'ar']);

        Mail::assertSent(LeadResultMail::class, function (LeadResultMail $mail) {
            $html = $this->renderMail($mail);

            return str_contains($html, 'dir="rtl"')
                && str_contains($html, 'نتيجة تقييمك جاهزة')
                && str_contains($mail->subjectLine, 'نتيجة');
        });

        $this->assertSame('ar', NotificationLog::query()->where('template_key', 'customer_result')->value('locale'));
    }

    public function test_an_english_submission_produces_english_emails(): void
    {
        Mail::fake();

        $this->get('/en/quiz')->assertOk();
        $this->submitQuiz(['locale' => 'en']);

        Mail::assertSent(LeadResultMail::class, function (LeadResultMail $mail) {
            $html = $this->renderMail($mail);

            return str_contains($html, 'dir="ltr"')
                && str_contains($html, 'Your result is ready')
                && str_contains($mail->subjectLine, 'Saudi-Ready Check result');
        });

        $this->assertSame('en', $this->latestLead()->locale);
        $this->assertSame('en', NotificationLog::query()->where('template_key', 'customer_result')->value('locale'));
    }

    /* ───────────────────────── 13. No queue anywhere ──────────────────────── */

    public function test_the_workflow_never_touches_a_queue(): void
    {
        Queue::fake();
        Bus::fake();
        Mail::fake();

        $this->submitQuiz();

        // Nothing was pushed: no worker, no supervisor, no cron needed.
        Queue::assertNothingPushed();
        Bus::assertNothingDispatched();

        // And the mail really was sent during this request.
        Mail::assertSent(LeadResultMail::class);
        Mail::assertSent(NewLeadMail::class);
    }

    public function test_no_mailable_is_marked_as_queueable(): void
    {
        foreach ([LeadResultMail::class, NewLeadMail::class, \App\Mail\LeadStatusMail::class, \App\Mail\TestMail::class] as $mailable) {
            $this->assertFalse(
                is_subclass_of($mailable, \Illuminate\Contracts\Queue\ShouldQueue::class),
                $mailable.' must send synchronously.'
            );
        }
    }

    public function test_the_jobs_table_stays_empty_after_a_submission(): void
    {
        Mail::fake();

        $this->submitQuiz();

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('failed_jobs')->count());
    }

    /* ───────────────────────────── helpers ────────────────────────────────── */

    protected function switchSetting(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => 'bool', 'group' => 'notifications'],
        );

        app(SettingsService::class)->flush();
    }

    /** Points the mailer at a dead port so every send throws. */
    protected function breakTheMailer(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 2,
            'mail.mailers.smtp.timeout' => 1,
            'mail.mailers.smtp.scheme' => 'smtp',
        ]);
    }
}
