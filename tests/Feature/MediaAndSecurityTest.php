<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\MediaService;
use App\Services\PexelsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MediaAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        Mail::fake();
    }

    public function test_pages_fall_back_to_local_images_without_an_api_key(): void
    {
        config(['services.pexels.key' => null]);
        Http::preventStrayRequests();

        $slot = app(MediaService::class)->slot('hero');

        $this->assertFalse($slot['remote']);
        $this->assertStringContainsString('images/fallback/hero.svg', $slot['url']);

        $this->get('/')->assertOk()->assertSee('images/fallback/hero.svg', false);
    }

    public function test_a_failing_pexels_request_never_breaks_the_page(): void
    {
        config(['services.pexels.key' => 'test-key']);
        Http::fake(['api.pexels.com/*' => Http::response('', 500)]);

        $this->assertNull(app(MediaService::class)->refresh('hero'));
        $this->get('/')->assertOk();
    }

    public function test_a_stored_asset_is_served_without_calling_the_api(): void
    {
        MediaAsset::create([
            'slot' => 'hero', 'provider' => 'pexels', 'query' => 'riyadh',
            'url' => 'https://images.pexels.com/photo.jpg', 'is_active' => true,
        ]);

        Http::preventStrayRequests();

        $this->get('/')->assertOk()->assertSee('https://images.pexels.com/photo.jpg', false);
    }

    public function test_pexels_results_are_normalised(): void
    {
        config(['services.pexels.key' => 'test-key']);
        Http::fake(['api.pexels.com/*' => Http::response([
            'photos' => [[
                'id' => 42,
                'src' => ['large2x' => 'https://images.pexels.com/42.jpg', 'medium' => 'https://images.pexels.com/42-m.jpg'],
                'photographer' => 'A Photographer',
                'avg_color' => '#123456',
            ]],
        ])]);

        $photos = app(PexelsClient::class)->search('riyadh skyline');

        $this->assertSame('https://images.pexels.com/42.jpg', $photos[0]['url']);
        $this->assertSame('A Photographer', $photos[0]['photographer']);
    }

    public function test_the_api_key_is_never_rendered_in_the_html(): void
    {
        config(['services.pexels.key' => 'super-secret-key']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('super-secret-key', $html);
        $this->assertStringNotContainsString('PEXELS', $html);
        $this->assertStringNotContainsString('GOOGLE_CLIENT_SECRET', $html);
        $this->assertStringNotContainsString('MAIL_PASSWORD', $html);
    }

    public function test_public_forms_are_csrf_protected(): void
    {
        // Laravel skips token validation while running tests, so assert the
        // middleware is in the web stack and the form actually carries a token.
        $web = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $web);

        $this->get('/quiz')->assertOk()->assertSee('name="_token"', false);
    }

    public function test_the_honeypot_blocks_bots(): void
    {
        $this->submitQuiz(['website' => 'http://spam.example'])->assertSessionHasErrors('website');
        $this->assertSame(0, \App\Models\Lead::query()->count());
    }

    public function test_submitted_text_is_escaped_on_the_result_page(): void
    {
        $this->submitQuiz(['name' => '<script>alert(1)</script>']);
        $lead = $this->latestLead();

        $html = $this->get(route('result', ['lead' => $lead->uuid]))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_the_submit_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $response = $this->post('/quiz/submit', $this->leadPayload(['email' => "x{$i}@example.com"]));
            $this->flushSession();
        }

        $response->assertStatus(429);
    }

    public function test_lead_deletion_is_restricted_to_admins(): void
    {
        $this->submitQuiz();
        $lead = $this->latestLead();
        $this->flushSession();

        $this->actingAs($this->admin('sales'));
        $this->delete('/admin/leads/'.$lead->id)->assertForbidden();

        $this->actingAs($this->admin('admin'));
        $this->delete('/admin/leads/'.$lead->id)->assertRedirect();
        $this->assertSame(0, \App\Models\Lead::query()->count());
    }
}
