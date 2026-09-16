<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Support\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        $this->seed(\Database\Seeders\EnglishContentSeeder::class);
        Mail::fake();
    }

    public function test_arabic_keeps_the_original_urls(): void
    {
        $this->get('/')->assertOk()->assertSee('السعودية مستنياك', false);
        $this->get('/quiz')->assertOk();

        $this->assertSame(url('/'), route('landing'));
    }

    public function test_english_is_served_from_the_en_prefix(): void
    {
        $response = $this->get('/en');

        $response->assertOk()
            ->assertSee('Saudi Arabia is waiting for you', false)
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false)
            ->assertDontSee('السعودية مستنياك', false);
    }

    public function test_the_quiz_content_itself_is_translated(): void
    {
        $this->get('/en/quiz')
            ->assertOk()
            ->assertSee('What stage is your company at?', false)
            ->assertSee('An established company', false);

        // The question payload is embedded as escaped JSON, so assert on the
        // Arabic copy that is rendered straight into the markup.
        $this->get('/quiz')
            ->assertOk()
            ->assertSee('اختار الإجابة الأقرب لوضع شركتك', false)
            ->assertDontSee('What stage is your company at?', false);
    }

    public function test_pages_declare_canonical_and_hreflang_alternates(): void
    {
        $response = $this->get('/en');

        $response->assertSee('rel="canonical"', false)
            ->assertSee('hreflang="ar"', false)
            ->assertSee('hreflang="en"', false)
            ->assertSee('hreflang="x-default"', false)
            ->assertSee('og:locale', false);
    }

    public function test_the_language_switcher_persists_the_choice(): void
    {
        $this->get(route('language.switch', ['locale' => 'en', 'redirect' => '/en/quiz']))
            ->assertRedirect(url('/en/quiz'));

        $this->assertSame('en', session(Locale::SESSION_KEY));

        // A locale-less endpoint now follows the stored language.
        $this->post('/quiz/state', ['answers' => [], 'index' => 0])->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_the_switcher_never_redirects_off_site(): void
    {
        $this->get(route('language.switch', ['locale' => 'en', 'redirect' => 'https://evil.example.com/steal']))
            ->assertRedirect(url('/en'));
    }

    public function test_an_unsupported_locale_is_rejected(): void
    {
        $this->get('/language/de')->assertNotFound();
    }

    public function test_the_ar_prefix_redirects_to_the_canonical_arabic_url(): void
    {
        $this->get('/ar/quiz')->assertRedirect('/quiz');
        $this->get('/ar')->assertRedirect('/');
    }

    public function test_validation_messages_follow_the_language(): void
    {
        // The form carries the language it was rendered in.
        $this->post('/quiz/submit', $this->leadPayload(['name' => '', 'consent' => null, 'locale' => 'en']))
            ->assertSessionHasErrors('name');

        $errors = session('errors')->get('name');
        $this->assertStringContainsString('name', strtolower($errors[0]), 'Expected an English validation message.');

        $this->post('/quiz/submit', $this->leadPayload(['name' => '', 'locale' => 'ar']))
            ->assertSessionHasErrors('name');

        $this->assertStringContainsString('الاسم', session('errors')->get('name')[0]);
    }

    public function test_the_lead_records_the_language_it_was_taken_in(): void
    {
        $this->get('/en/quiz')->assertOk();
        $this->submitQuiz(['locale' => 'en']);

        $this->assertSame('en', $this->latestLead()->locale);
    }

    public function test_result_pages_render_in_the_language_of_the_lead(): void
    {
        $this->get('/en/quiz')->assertOk();
        $this->submitQuiz(['locale' => 'en']);
        $lead = $this->latestLead();

        $this->get(route('en.result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('Saudi Arabia really is waiting for you', false);

        $this->get(route('result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('السعودية مستنياك فعلًا', false);
    }

    public function test_the_stored_answers_are_translated_on_the_result_page(): void
    {
        $this->get('/en/quiz')->assertOk();
        $this->submitQuiz(['locale' => 'en']);
        $lead = $this->latestLead();

        // The "main question" label is stored in Arabic at submit time; the
        // English page must still render the English option label.
        $this->get(route('en.result', ['lead' => $lead->uuid]))
            ->assertOk()
            ->assertSee('Setup cost', false)
            ->assertDontSee('تكلفة التأسيس', false);
    }

    public function test_qr_links_keep_working_and_land_on_arabic(): void
    {
        $this->get('/qr/booth_qr')->assertRedirect(route('landing', ['source' => 'booth_qr']));
    }

    public function test_admin_is_available_in_both_languages(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin')->assertOk()->assertSee('العملاء المحتملون', false);

        $this->get(route('language.switch', ['locale' => 'en', 'redirect' => '/admin']));
        $this->get('/admin')->assertOk()->assertSee('Leads', false);
    }
}
