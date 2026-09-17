<?php

namespace Tests\Feature;

use App\Support\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for the production 500:
 *
 *   TypeError: App\Support\Locale::meta(): Return value must be of type array,
 *   null returned — app/Support/Locale.php:41
 *
 * The server was running a `bootstrap/cache/config.php` built by an earlier
 * release, so `config('creativemark.locales')` did not exist. meta() looked the
 * language up in config only, found nothing for `en` and nothing for the
 * fallback either, and returned null into an `array` return type — which took
 * down every page, because the public layout calls it for <html lang dir>.
 *
 * These tests pin the contract: the locale system is answerable from code alone.
 */
class LocaleResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
        $this->seed(\Database\Seeders\EnglishContentSeeder::class);
    }

    /** The exact production state: config cache from a release without these keys. */
    protected function simulateStaleConfigCache(): void
    {
        config([
            'creativemark.locales' => null,
            'creativemark.base_locale' => null,
            'app.locale' => 'en',
            'app.fallback_locale' => 'en',
        ]);
    }

    public function test_meta_returns_an_array_for_every_supported_locale(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $meta = Locale::meta($locale);

            $this->assertIsArray($meta);
            foreach (Locale::META_KEYS as $key) {
                $this->assertArrayHasKey($key, $meta);
                $this->assertNotSame('', $meta[$key]);
            }
        }

        $this->assertSame('rtl', Locale::meta('ar')['dir']);
        $this->assertSame('ltr', Locale::meta('en')['dir']);
        $this->assertSame('ar', Locale::meta('ar')['html']);
        $this->assertSame('en', Locale::meta('en')['html']);
    }

    public function test_meta_falls_back_instead_of_returning_null(): void
    {
        $this->assertIsArray(Locale::meta(null));
        $this->assertIsArray(Locale::meta('invalid'));
        $this->assertIsArray(Locale::meta(''));
        $this->assertIsArray(Locale::meta('de'));

        $this->assertSame(Locale::meta(Locale::default()), Locale::meta('invalid'));
    }

    public function test_regional_codes_are_normalised(): void
    {
        $this->assertSame('en', Locale::normalise('en-GB'));
        $this->assertSame('en', Locale::normalise('en_US'));
        $this->assertSame('ar', Locale::normalise('ar-SA'));
        $this->assertSame(Locale::default(), Locale::normalise('zz'));
        $this->assertSame('ltr', Locale::meta('en-GB')['dir']);
    }

    public function test_meta_survives_a_stale_config_cache(): void
    {
        $this->simulateStaleConfigCache();

        foreach ([null, 'ar', 'en', 'invalid', ''] as $locale) {
            $this->assertIsArray(Locale::meta($locale), "meta({$locale}) must never return null");
        }

        $this->assertSame(['ar', 'en'], Locale::supported());
        $this->assertSame('ar', Locale::default(), 'Arabic stays the default even when APP_LOCALE=en.');
        $this->assertSame('rtl', Locale::direction('ar'));
        $this->assertSame('ltr', Locale::direction('en'));
        $this->assertNotEmpty(Locale::all());
    }

    public function test_config_can_still_override_the_built_in_metadata(): void
    {
        config(['creativemark.locales.en' => ['native' => 'English (UK)', 'iso' => 'en_US']]);

        $meta = Locale::meta('en');

        $this->assertSame('English (UK)', $meta['native']);
        $this->assertSame('en_US', $meta['iso']);
        $this->assertSame('ltr', $meta['dir'], 'Keys absent from config still come from code.');
    }

    public function test_malformed_config_cannot_break_the_contract(): void
    {
        config(['creativemark.locales.ar' => ['dir' => '', 'html' => null, 'native' => 'عربي']]);

        $meta = Locale::meta('ar');

        $this->assertSame('rtl', $meta['dir']);
        $this->assertSame('ar', $meta['html']);
        $this->assertSame('عربي', $meta['native']);
    }

    public function test_every_public_page_renders_with_a_stale_config_cache(): void
    {
        $this->simulateStaleConfigCache();

        $this->get('/')->assertOk();
        $this->get('/quiz')->assertOk();
        $this->get('/en')->assertOk();
        $this->get('/en/quiz')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_pages_keep_their_language_when_app_locale_is_english(): void
    {
        // Production ships APP_LOCALE=en while Arabic must stay the site default.
        config(['app.locale' => 'en', 'app.fallback_locale' => 'en']);

        $this->get('/')->assertOk()->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false);
        $this->get('/quiz')->assertOk()->assertSee('lang="ar"', false);
        $this->get('/en')->assertOk()->assertSee('lang="en"', false)->assertSee('dir="ltr"', false);
        $this->get('/en/quiz')->assertOk()->assertSee('lang="en"', false);
    }

    public function test_an_invalid_locale_in_the_session_never_causes_a_500(): void
    {
        $this->withSession([Locale::SESSION_KEY => 'zz-ZZ'])->get('/')->assertOk();
        $this->withSession([Locale::SESSION_KEY => ''])->get('/quiz')->assertOk();
        $this->withCookie(Locale::COOKIE, 'nonsense')->get('/')->assertOk();
    }

    public function test_an_invalid_locale_in_the_url_is_not_routed(): void
    {
        $this->get('/de')->assertNotFound();
        $this->get('/de/quiz')->assertNotFound();
        $this->get('/language/zz')->assertNotFound();
    }

    public function test_emails_render_with_a_stale_config_cache(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $this->submitQuiz();
        $lead = $this->latestLead();

        $this->simulateStaleConfigCache();

        $this->assertStringContainsString('dir="ltr"', $this->renderMail(
            new \App\Mail\LeadResultMail($lead, null, null, null, null, 'en')
        ));

        // An unknown language must still render, in the fallback direction.
        $this->assertStringContainsString('dir="rtl"', $this->renderMail(
            new \App\Mail\LeadResultMail($lead, null, null, null, null, 'invalid')
        ));
    }

    public function test_route_names_resolve_for_any_locale_input(): void
    {
        $this->assertSame('landing', Locale::routeName('landing', 'ar'));
        $this->assertSame('en.landing', Locale::routeName('landing', 'en'));
        $this->assertSame('en.landing', Locale::routeName('en.landing', 'en'));
        $this->assertSame('landing', Locale::routeName('en.landing', 'ar'));
        $this->assertSame('landing', Locale::routeName('landing', 'invalid'));
    }
}
