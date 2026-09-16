<?php

namespace Tests\Feature;

use App\Exports\LeadsExport;
use App\Models\Lead;
use App\Models\QuizQuestion;
use App\Models\ResultRule;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        Mail::fake();
    }

    protected function withLead(int $score = 12): Lead
    {
        $this->submitQuiz([], $this->answersScoring($score));
        $lead = $this->latestLead();
        $this->flushSession();

        return $lead;
    }

    public function test_every_admin_page_renders(): void
    {
        $lead = $this->withLead();
        $this->actingAs($this->admin());

        $pages = [
            '/admin', '/admin/leads', '/admin/leads/'.$lead->id, '/admin/analytics',
            '/admin/notifications', '/admin/notification-templates',
            '/admin/quiz', '/admin/quiz/create', '/admin/results', '/admin/results/create',
            '/admin/events', '/admin/events/create', '/admin/qr-sources',
            '/admin/cms', '/admin/media', '/admin/settings', '/admin/users',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_the_dashboard_shows_an_empty_state_before_the_first_lead(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin')->assertOk()->assertSee('No leads yet', false);
    }

    public function test_leads_can_be_filtered_and_searched(): void
    {
        $hot = $this->withLead(12);
        $early = $this->withLead(0);
        $early->update(['name' => 'Mona Farid', 'company' => 'Early Co']);

        // The header notification tray also lists lead names — clear it so the
        // assertions below only look at the table itself.
        \App\Models\AdminNotification::query()->delete();

        $this->actingAs($this->admin());

        $this->get('/admin/leads?result=ready&range=all')
            ->assertOk()
            ->assertSee($hot->company)
            ->assertDontSee('Early Co');

        $this->get('/admin/leads?q=Mona&range=all')
            ->assertOk()
            ->assertSee('Early Co')
            ->assertDontSee($hot->company);

        $this->get('/admin/leads?sector=tech&range=all')->assertOk()->assertSee($hot->company);
        $this->get('/admin/leads?sector=food&range=all')->assertOk()->assertDontSee($hot->company);
    }

    public function test_sales_status_and_notes_are_recorded(): void
    {
        $lead = $this->withLead();
        $this->actingAs($this->admin());

        $this->patch('/admin/leads/'.$lead->id.'/status', ['sales_status' => 'meeting'])->assertRedirect();
        $this->assertSame('meeting', $lead->fresh()->sales_status);
        $this->assertDatabaseHas('lead_notes', ['lead_id' => $lead->id, 'type' => 'status']);

        $this->post('/admin/leads/'.$lead->id.'/notes', ['body' => 'اتكلمنا معاه'])->assertRedirect();
        $this->assertDatabaseHas('lead_notes', ['lead_id' => $lead->id, 'body' => 'اتكلمنا معاه', 'type' => 'note']);

        $this->patch('/admin/leads/'.$lead->id.'/assign', ['assigned_to' => auth()->id()])->assertRedirect();
        $this->assertSame(auth()->id(), $lead->fresh()->assigned_to);
    }

    public function test_an_unknown_sales_status_is_rejected(): void
    {
        $lead = $this->withLead();
        $this->actingAs($this->admin());

        $this->patch('/admin/leads/'.$lead->id.'/status', ['sales_status' => 'invented'])
            ->assertSessionHasErrors('sales_status');
    }

    public function test_leads_export_as_an_excel_file(): void
    {
        $this->withLead();
        $this->actingAs($this->admin());

        $response = $this->get('/admin/leads/export?range=all');

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
        $this->assertStringContainsString('creative-mark-leads-', $response->headers->get('content-disposition'));
    }

    public function test_the_quiz_builder_creates_questions_and_options(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/quiz', [
            'key' => 'team_size', 'type' => 'single', 'title' => 'حجم فريقك؟',
            'is_required' => '1', 'is_scored' => '1', 'is_active' => '1',
        ])->assertRedirect();

        $question = QuizQuestion::query()->where('key', 'team_size')->firstOrFail();

        $this->post('/admin/quiz/'.$question->id.'/options', [
            'key' => 'small', 'label' => 'أقل من 10', 'score' => 1, 'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('quiz_options', ['quiz_question_id' => $question->id, 'key' => 'small', 'score' => 1]);

        // The new question shows up in the public quiz immediately.
        $this->get('/quiz')->assertOk()->assertSee('team_size', false);

        $this->patch('/admin/quiz/'.$question->id.'/toggle')->assertRedirect();
        $this->assertFalse($question->fresh()->is_active);

        $this->delete('/admin/quiz/'.$question->id)->assertRedirect();
        $this->assertDatabaseMissing('quiz_questions', ['key' => 'team_size']);
    }

    public function test_questions_can_be_reordered(): void
    {
        $this->actingAs($this->admin());

        $ids = QuizQuestion::query()->orderBy('position')->pluck('id')->all();
        $reversed = array_reverse($ids);

        $this->post('/admin/quiz/reorder', ['order' => $reversed])->assertOk();

        $this->assertSame($reversed, QuizQuestion::query()->orderBy('position')->pluck('id')->all());

        // The public quiz follows the new order.
        auth()->logout();
        $first = QuizQuestion::query()->orderBy('position')->first();
        $this->get('/quiz')->assertOk()->assertSeeInOrder([$first->key, QuizQuestion::query()->orderBy('position')->skip(1)->first()->key], false);
    }

    public function test_duplicate_question_keys_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/quiz', ['key' => 'sector', 'type' => 'single', 'title' => 'مكرر'])
            ->assertSessionHasErrors('key');
    }

    public function test_result_content_is_editable_and_reaches_the_public_page(): void
    {
        $lead = $this->withLead(12);
        $rule = ResultRule::query()->where('key', 'ready')->firstOrFail();

        $this->actingAs($this->admin());

        $this->put('/admin/results/'.$rule->id, [
            'key' => 'ready',
            'classification' => 'Hot Lead',
            'indicator' => 'green',
            'min_score' => 9,
            'max_score' => 12,
            'headline' => 'عنوان جديد للنتيجة',
            'main_text' => 'نص أساسي',
            'body' => 'شرح',
            'bullets_text' => "أولًا\nثانيًا",
            'highlight' => 'ملاحظة',
            'primary_cta_label' => 'احجز',
            'is_active' => '1',
        ])->assertRedirect();

        $rule->refresh();
        $this->assertSame('عنوان جديد للنتيجة', $rule->headline);
        $this->assertSame(['أولًا', 'ثانيًا'], $rule->bullets);

        $this->get(\Illuminate\Support\Facades\URL::signedRoute('result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('عنوان جديد للنتيجة', false);
    }

    public function test_cms_content_updates_the_landing_page(): void
    {
        $this->actingAs($this->admin());

        $this->put('/admin/cms', [
            'content' => ['hero_kicker' => 'نص الهيرو الجديد', 'cta_label' => 'يلا نبدأ'],
            'benefits' => [['icon' => '⚡', 'title' => 'سريع', 'text' => 'خلال دقيقة']],
            'seo_title' => 'Saudi-Ready Check',
        ])->assertRedirect();

        $this->get('/')->assertOk()->assertSee('نص الهيرو الجديد', false)->assertSee('يلا نبدأ', false);
    }

    public function test_cta_settings_drive_the_public_buttons(): void
    {
        $lead = $this->withLead(12);
        $this->actingAs($this->admin());

        $this->put('/admin/settings', [
            'settings' => [
                'cta_booking_url' => 'https://example.com/book',
                'cta_whatsapp_url' => 'https://wa.me/966500000000',
                'notify_admin' => '1',
            ],
        ])->assertRedirect();

        app(SettingsService::class)->flush();

        $this->get(\Illuminate\Support\Facades\URL::signedRoute('result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('https://example.com/book', false)
            ->assertSee('https://wa.me/966500000000', false);
    }

    public function test_invalid_setting_urls_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->put('/admin/settings', ['settings' => ['cta_booking_url' => 'not a url']])
            ->assertSessionHasErrors('cta_booking_url');

        $this->assertSame('', (string) Setting::query()->where('key', 'cta_booking_url')->value('value'));
    }

    public function test_boolean_settings_are_only_changed_when_submitted(): void
    {
        $this->actingAs($this->admin());

        // A form that does not render the toggle must never switch it off —
        // this is what silently stopped the customer emails in production.
        $this->put('/admin/settings', ['settings' => ['cta_booking_url' => 'https://example.com/book']])->assertRedirect();
        $this->assertSame('1', Setting::query()->where('key', 'notify_customer')->value('value'));

        // Submitting the explicit 0 (hidden input) does switch it off.
        $this->put('/admin/settings', ['settings' => ['notify_customer' => '0']])->assertRedirect();
        $this->assertSame('0', Setting::query()->where('key', 'notify_customer')->value('value'));

        $this->put('/admin/settings', ['settings' => ['notify_customer' => '1']])->assertRedirect();
        $this->assertSame('1', Setting::query()->where('key', 'notify_customer')->value('value'));
    }

    public function test_qr_sources_can_be_created_and_track_leads(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/qr-sources', ['name' => 'Speaker QR', 'slug' => 'speaker_qr', 'is_active' => '1'])->assertRedirect();
        $this->assertDatabaseHas('qr_sources', ['slug' => 'speaker_qr']);

        auth()->logout();
        $this->flushSession();

        $this->get('/?source=speaker_qr')->assertOk();
        $this->submitQuiz();

        $this->assertSame('speaker_qr', $this->latestLead()->qrSource->slug);
    }

    public function test_events_can_be_created_and_set_as_default(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/events', [
            'name' => 'TECHNE — Cairo 2027', 'status' => 'active', 'city' => 'Cairo', 'is_default' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('events', ['slug' => 'techne-cairo-2027', 'is_default' => true]);
        $this->assertSame(1, \App\Models\Event::query()->where('is_default', true)->count());
    }

    public function test_notification_templates_are_editable(): void
    {
        $this->actingAs($this->admin());

        $template = \App\Models\NotificationTemplate::query()->where('key', 'admin_new_lead')->firstOrFail();

        $this->put('/admin/notification-templates/'.$template->id, [
            'name' => 'Sales alert', 'subject' => 'Lead: {{name}}', 'body' => '<p>{{company}} — {{score}}</p>', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('Lead: {{name}}', $template->fresh()->subject);
    }

    public function test_notifications_can_be_marked_as_read(): void
    {
        $this->withLead();
        $this->actingAs($this->admin());

        $this->post('/admin/notifications/read-all')->assertRedirect();

        $this->assertSame(0, \App\Models\AdminNotification::query()->whereNull('read_at')->count());
    }

    public function test_users_can_be_created_by_an_admin(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/users', [
            'name' => 'Sales One', 'email' => 'sales1@creativemark.test', 'role' => 'sales',
            'password' => 'StrongPass2026', 'password_confirmation' => 'StrongPass2026',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'sales1@creativemark.test', 'role' => 'sales']);
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/users', [
            'name' => 'Weak', 'email' => 'weak@creativemark.test', 'role' => 'sales',
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
