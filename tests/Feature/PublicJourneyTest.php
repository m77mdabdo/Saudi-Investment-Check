<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\Lead;
use App\Models\QrSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        Mail::fake();
    }

    public function test_landing_page_renders_and_records_a_view(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('السعودية مستنياك', false)
            ->assertSee('ابدأ الرحله', false);

        $this->assertDatabaseHas('analytics_events', ['name' => 'landing_page_view']);
    }

    public function test_quiz_page_renders_all_active_questions(): void
    {
        $response = $this->get('/quiz');

        $response->assertOk()
            ->assertSee('company_stage', false)
            ->assertSee('main_question', false);

        $this->assertDatabaseHas('analytics_events', ['name' => 'quiz_started']);
    }

    public function test_quiz_state_is_persisted_in_the_session(): void
    {
        $this->post('/quiz/state', ['answers' => ['company_stage' => 'idea'], 'index' => 1])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('idea', session(config('creativemark.quiz.session_key').'.answers.company_stage'));

        // Restored on the next page load.
        $this->get('/quiz')->assertOk()->assertSee('idea', false);
    }

    public function test_a_complete_submission_creates_a_scored_lead(): void
    {
        $response = $this->submitQuiz();

        $lead = $this->latestLead();

        $this->assertNotNull($lead);
        $response->assertRedirect(route('result', ['lead' => $lead->uuid]));

        $this->assertSame(12, $lead->score);
        $this->assertSame(12, $lead->max_score);
        $this->assertSame('ready', $lead->result_key);
        $this->assertSame('Hot Lead', $lead->classification);
        $this->assertSame('+201000000000', $lead->whatsapp);
        $this->assertTrue($lead->consent);
        $this->assertSame('new', $lead->sales_status);
        $this->assertCount(8, $lead->answers);
        $this->assertSame('تكلفة التأسيس', $lead->mainQuestion());
    }

    public function test_the_result_page_shows_the_matching_rule(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();

        $this->get(route('result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('السعودية مستنياك فعلًا', false)
            ->assertSee('12');
    }

    public function test_a_result_cannot_be_opened_by_someone_else(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();

        $this->flushSession();

        $this->get(route('result', ['lead' => $lead->uuid]))->assertRedirect(route('landing'));
    }

    public function test_a_signed_result_url_is_accepted(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();
        $signed = \Illuminate\Support\Facades\URL::signedRoute('result', ['lead' => $lead->uuid]);

        $this->flushSession();

        $this->get($signed)->assertOk();
    }

    public function test_consent_is_required(): void
    {
        $this->submitQuiz(['consent' => null])->assertSessionHasErrors('consent');

        $this->assertSame(0, Lead::query()->count());
    }

    public function test_contact_fields_are_required(): void
    {
        $this->submitQuiz(['name' => '', 'company' => '', 'phone' => ''])
            ->assertSessionHasErrors(['name', 'company', 'phone']);
    }

    public function test_answers_from_outside_the_quiz_are_rejected(): void
    {
        $answers = $this->perfectAnswers();
        $answers['company_stage'] = 'not-a-real-option';

        $this->submitQuiz([], $answers)->assertSessionHasErrors('answers.company_stage');
        $this->assertSame(0, Lead::query()->count());
    }

    public function test_scores_are_never_taken_from_the_request(): void
    {
        $this->submitQuiz(['score' => 999, 'result_key' => 'ready'], $this->answersScoring(0));

        $lead = $this->latestLead();

        $this->assertSame(0, $lead->score);
        $this->assertSame('early', $lead->result_key);
    }

    public function test_the_other_sector_detail_is_stored(): void
    {
        $answers = $this->perfectAnswers();
        $answers['sector'] = 'other';
        $answers['sector_detail'] = 'خدمات بحرية';

        $this->submitQuiz([], $answers);

        $lead = $this->latestLead();

        $this->assertSame('خدمات بحرية', $lead->answers()->where('question_key', 'sector')->value('answer_text'));
        $this->assertSame('خدمات بحرية', $lead->answers_summary['sector_other']);
    }

    public function test_duplicate_submissions_reuse_the_first_lead(): void
    {
        $this->submitQuiz();
        $first = $this->latestLead();

        $this->submitQuiz()->assertRedirect(route('result', ['lead' => $first->uuid]));

        $this->assertSame(1, Lead::query()->count());
    }

    public function test_qr_source_and_utm_attribution_follow_the_visitor(): void
    {
        $qr = QrSource::query()->where('slug', 'booth_qr')->firstOrFail();

        $this->get('/?source=booth_qr&utm_campaign=techne_2026&utm_medium=qr')->assertOk();
        $this->get('/quiz')->assertOk();
        $this->submitQuiz();

        $lead = $this->latestLead();

        $this->assertSame($qr->id, $lead->qr_source_id);
        $this->assertSame('booth_qr', $lead->source);
        $this->assertSame('techne_2026', $lead->utm_campaign);
        $this->assertNotNull($lead->event_id);
        $this->assertSame(1, $qr->fresh()->scans);
    }

    public function test_the_short_qr_route_redirects_with_the_source(): void
    {
        $this->get('/qr/walking_qr')->assertRedirect(route('landing', ['source' => 'walking_qr']));
    }

    public function test_funnel_events_are_recorded(): void
    {
        $this->get('/')->assertOk();
        $this->get('/quiz')->assertOk();
        $this->post('/quiz/completed')->assertOk();
        $this->submitQuiz();

        $names = AnalyticsEvent::query()->pluck('name')->unique();

        foreach (['landing_page_view', 'quiz_started', 'quiz_completed', 'lead_form_viewed', 'lead_submitted', 'result_ready'] as $expected) {
            $this->assertTrue($names->contains($expected), "Missing analytics event: {$expected}");
        }
    }

    public function test_cta_clicks_are_tracked(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();

        $this->post('/track', ['name' => 'meeting_clicked', 'label' => 'result_primary', 'lead' => $lead->uuid])
            ->assertOk();

        $this->assertDatabaseHas('analytics_events', ['name' => 'meeting_clicked', 'lead_id' => $lead->id]);
    }

    public function test_unknown_tracking_events_are_rejected(): void
    {
        $this->post('/track', ['name' => 'drop_table'])->assertSessionHasErrors('name');
    }

    public function test_answering_the_quiz_does_not_exhaust_the_submit_rate_limit(): void
    {
        // Each endpoint has its own limiter — the autosave/progress beacons a
        // visitor fires while answering must never block the final submit.
        for ($i = 0; $i < 40; $i++) {
            $this->post('/quiz/state', ['answers' => ['company_stage' => 'idea'], 'index' => 1])->assertOk();
            $this->post('/quiz/progress', ['question' => 'company_stage'])->assertOk();
        }

        $this->submitQuiz()->assertRedirect();
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_notifications_are_sent_and_logged(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();

        Mail::assertSent(\App\Mail\TemplatedMail::class, 2);

        $this->assertDatabaseHas('notification_logs', ['lead_id' => $lead->id, 'template_key' => 'admin_new_lead', 'status' => 'sent']);
        $this->assertDatabaseHas('notification_logs', ['lead_id' => $lead->id, 'template_key' => 'customer_result', 'status' => 'sent']);
        $this->assertDatabaseHas('admin_notifications', ['lead_id' => $lead->id, 'type' => 'lead']);
    }

    public function test_the_customer_email_is_skipped_without_an_address(): void
    {
        $this->submitQuiz(['email' => null]);

        Mail::assertSent(\App\Mail\TemplatedMail::class, 1);
    }
}
