<?php

namespace Tests\Feature;

use App\Models\EventRegistration;
use App\Services\PhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();

        // Without this the suite writes real .webp files into
        // storage/app/private/registrations and never removes them — 45 had
        // accumulated against zero rows before this was added. fake() swaps in
        // a temp disk that Laravel discards between tests.
        Storage::fake(PhotoStorage::DISK);
    }

    /** A real JPEG with EXIF orientation + GPS, so the pipeline is genuinely exercised. */
    protected function photo(int $width = 2400, int $height = 1800): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x += 6) {
            for ($y = 0; $y < $height; $y += 6) {
                imagesetpixel($image, $x, $y, imagecolorallocate($image, $x % 255, $y % 255, 120));
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'reg').'.jpg';
        imagejpeg($image, $path, 90);
        imagedestroy($image);

        return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
    }

    public function test_form_renders_in_the_landing_hero_in_both_locales(): void
    {
        $this->get('/')->assertOk()->assertSee(__('registration.heading', [], 'ar'));
        $this->get('/en')->assertOk()->assertSee(__('registration.heading', [], 'en'));
    }

    public function test_the_quiz_is_out_of_this_flow(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString(__('home.cta_label', [], 'ar'), $html);
        $this->assertStringNotContainsString('start_quiz', $html);
    }

    public function test_photo_input_offers_camera_and_gallery(): void
    {
        // accept="image/*" WITHOUT capture: capture would force the camera and
        // remove the gallery option.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('accept="image/*"', $html);
        $this->assertStringNotContainsString('capture', $html);
    }

    public function test_registers_with_name_and_phone_only(): void
    {
        $this->post('/register', [
            'name' => 'Minimal Visitor',
            'country_code' => '+966',
            'phone' => '512345678',
        ])->assertRedirect();

        $registration = EventRegistration::first();

        $this->assertSame('Minimal Visitor', $registration->name);
        $this->assertSame('+966512345678', $registration->phone);
        $this->assertNull($registration->email);
        $this->assertNull($registration->photo_path);
    }

    public function test_registers_with_a_photo_and_stores_it_privately(): void
    {
        $this->post('/register', [
            'name' => 'Visitor With Photo',
            'country_code' => '+966',
            'phone' => '551234567',
            'email' => 'visitor@example.com',
            'photo' => $this->photo(),
        ])->assertRedirect();

        $registration = EventRegistration::first();

        $this->assertNotNull($registration->photo_path);
        $this->assertTrue(Storage::disk(PhotoStorage::DISK)->exists($registration->photo_path));

        // Private disk only — never the public one behind the symlink.
        $this->assertFalse(Storage::disk('public')->exists($registration->photo_path));
    }

    public function test_photo_is_downscaled_stripped_and_stored_as_webp(): void
    {
        $this->post('/register', [
            'name' => 'Pipeline', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(2400, 1800),
        ])->assertRedirect();

        $registration = EventRegistration::first();
        $binary = Storage::disk(PhotoStorage::DISK)->get($registration->photo_path);

        $info = getimagesizefromstring($binary);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertLessThanOrEqual(1600, max($info[0], $info[1]), 'longest edge must be capped at 1600');
        $this->assertStringEndsWith('.webp', $registration->photo_path);
        $this->assertStringNotContainsString('Exif', $binary);
    }

    public function test_rejects_a_bad_phone(): void
    {
        $this->post('/register', [
            'name' => 'Bad Phone', 'country_code' => '+966', 'phone' => 'not-a-number',
        ])->assertSessionHasErrors('phone');

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_an_oversized_photo_is_dropped_but_the_person_is_kept(): void
    {
        // Previously this asserted the submit was rejected outright. That was
        // the bug: an optional field discarded a whole registration.
        $this->post('/register', [
            'name' => 'Too Big', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg'),
        ])->assertRedirect()->assertSessionHas('photo_failed', true);

        $this->assertSame(1, EventRegistration::count());
        $this->assertNull(EventRegistration::first()->photo_path);
    }

    public function test_rejects_a_non_image_disguised_as_one(): void
    {
        // mimetypes: sniffs content, so an .jpg that is really text is refused.
        $path = tempnam(sys_get_temp_dir(), 'fake').'.jpg';
        file_put_contents($path, 'this is definitely not an image');

        $this->post('/register', [
            'name' => 'Disguised', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true),
        ])->assertRedirect()->assertSessionHas('photo_failed', true);

        // The file is still refused — it is simply not allowed to take the
        // registration down with it.
        $this->assertSame(1, EventRegistration::count());
        $this->assertNull(EventRegistration::first()->photo_path);
    }

    public function test_guest_cannot_read_a_photo(): void
    {
        $this->post('/register', [
            'name' => 'Private', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ]);

        $registration = EventRegistration::first();

        $this->get(route('admin.registrations.photo', $registration))
            ->assertRedirect(route('admin.login'));
    }

    public function test_signed_in_admin_can_read_a_photo(): void
    {
        $this->post('/register', [
            'name' => 'Visible To Staff', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ]);

        $registration = EventRegistration::first();

        $this->actingAs($this->admin())
            ->get(route('admin.registrations.photo', $registration))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');
    }

    public function test_admin_can_list_and_delete_a_photo(): void
    {
        $this->post('/register', [
            'name' => 'Listed', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ]);

        $registration = EventRegistration::first();
        $path = $registration->photo_path;

        $this->actingAs($this->admin())->get(route('admin.registrations.index'))
            ->assertOk()->assertSee('Listed');

        $this->actingAs($this->admin())
            ->delete(route('admin.registrations.photo.destroy', $registration))
            ->assertRedirect();

        $this->assertFalse(Storage::disk(PhotoStorage::DISK)->exists($path));
        $this->assertNull($registration->fresh()->photo_path);
    }

    public function test_export_has_exactly_three_columns_and_leaks_nothing(): void
    {
        EventRegistration::create([
            'name' => 'Export Probe', 'phone' => '+966512345678', 'email' => 'probe@example.com',
            'photo_path' => 'registrations/secret.webp', 'source' => 'booth_qr',
            'device' => 'mobile', 'locale' => 'ar', 'ip_hash' => str_repeat('a', 64),
        ]);

        $csv = \Maatwebsite\Excel\Facades\Excel::raw(
            new \App\Exports\RegistrationsExport([], app(\App\Http\Controllers\Admin\RegistrationController::class)),
            \Maatwebsite\Excel\Excel::CSV,
        );

        $rows = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertSame(['Name', 'Email', 'Phone'], str_getcsv($rows[0]));
        $this->assertCount(3, str_getcsv($rows[1]));

        // The photo is viewable in the dashboard and must never be exported,
        // and nothing else may ride along either.
        foreach (['secret.webp', 'booth_qr', 'mobile', str_repeat('a', 64)] as $leak) {
            $this->assertStringNotContainsString($leak, $csv);
        }

        // The default value binder turns "+966..." into a number and eats the +.
        $this->assertStringContainsString('+966512345678', $csv);
    }

    public function test_removed_cms_fields_keep_their_stored_values(): void
    {
        $this->actingAs($this->admin());

        $page = \App\Models\LandingPage::firstOrCreate(['slug' => 'default'], ['name' => 'Landing', 'content' => []]);
        $page->update(['content' => ['hero_kicker' => 'kept', 'welcome_title' => 'before']]);

        $this->put('/admin/cms', ['content' => ['welcome_title' => 'after']])->assertRedirect();

        // hero_kicker is no longer editable, but the stored value survives.
        $this->assertSame('kept', $page->fresh()->content['hero_kicker']);
        $this->assertSame('after', $page->fresh()->content['welcome_title']);
    }

    public function test_event_badge_shows_name_and_dates(): void
    {
        \App\Models\Event::query()->update(['is_default' => false]);
        \App\Models\Event::create([
            'name' => 'TECHNE — Alexandria 2026', 'slug' => 'techne-2026',
            'starts_at' => '2026-09-20', 'ends_at' => '2026-09-22',
            'status' => 'active', 'is_default' => true,
        ]);

        $this->get('/')->assertOk()
            ->assertSee('TECHNE — Alexandria 2026 · 20 Sep 2026 — 22 Sep 2026', false);
    }

    public function test_event_badge_omits_the_separator_when_there_are_no_dates(): void
    {
        \App\Models\Event::query()->update(['is_default' => false]);
        \App\Models\Event::create([
            'name' => 'Undated Event', 'slug' => 'undated',
            'starts_at' => null, 'ends_at' => null,
            'status' => 'active', 'is_default' => true,
        ]);

        $html = $this->get('/')->assertOk()->assertSee('Undated Event', false)->getContent();

        // The name must not be followed by a dangling separator.
        $this->assertStringNotContainsString('Undated Event ·', $html);
    }

    public function test_event_badge_renders_nothing_without_an_active_event(): void
    {
        \App\Models\Event::query()->delete();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('cm-fade-up mb-3 text-xs', $html);
        $this->assertStringNotContainsString(' · ', $html);
    }

    public function test_the_page_carries_the_privacy_note_not_the_old_disclaimer(): void
    {
        $this->get('/')->assertOk()->assertSee(__('registration.privacy_note', [], 'ar'), false);
        $this->get('/en')->assertOk()->assertSee(__('registration.privacy_note', [], 'en'), false);
    }

    public function test_benefit_cards_describe_registration_not_the_quiz(): void
    {
        $this->get('/en')->assertOk()
            ->assertSee('Done in seconds', false)
            ->assertSee('The team calls you after the event.', false)
            ->assertDontSee('nothing to type', false)
            ->assertDontSee('A clear result', false);

        $this->get('/')->assertOk()
            ->assertSee('هنتواصل معاك', false)
            ->assertDontSee('من غير كتابة', false);
    }

    public function test_refresh_copy_command_is_idempotent_and_leaves_other_keys_alone(): void
    {
        $page = \App\Models\LandingPage::where('slug', 'default')->firstOrFail();

        // Put the row back into the quiz-era state production is serving.
        $page->content = array_replace((array) $page->content, [
            'benefits' => [['icon' => '⚡', 'title' => 'أقل من 60 ثانية', 'text' => 'كله اختيارات — من غير كتابة.']],
            'footer_note' => 'تقييم مبدئي لمستوى الجاهزية — وليس استشارة قانونية أو مالية.',
            'eyebrow' => 'DO NOT TOUCH',
        ]);
        $translations = (array) $page->translations;
        $translations['en']['content'] = array_replace((array) data_get($translations, 'en.content', []), [
            'benefits' => [['icon' => '⚡', 'title' => 'Under 60 seconds', 'text' => 'Tap to choose — nothing to type.']],
            'footer_note' => 'A preliminary readiness assessment — not legal or financial advice.',
            'eyebrow' => 'DO NOT TOUCH EN',
        ]);
        $page->translations = $translations;
        $page->save();

        $before = count((array) $page->fresh()->content);

        $this->artisan('app:refresh-registration-copy')->assertSuccessful();

        $page = $page->fresh();
        $ar = (array) $page->content;
        $en = (array) data_get($page->translations, 'en.content', []);

        $this->assertSame('ثواني وتخلص', $ar['benefits'][0]['title']);
        $this->assertSame('Done in seconds', $en['benefits'][0]['title']);
        $this->assertSame(__('registration.privacy_note', [], 'ar'), $ar['footer_note']);
        $this->assertSame(__('registration.privacy_note', [], 'en'), $en['footer_note']);

        // Nothing else moved.
        $this->assertSame('DO NOT TOUCH', $ar['eyebrow']);
        $this->assertSame('DO NOT TOUCH EN', $en['eyebrow']);
        $this->assertCount($before, $ar);

        // Safe to run twice.
        $this->artisan('app:refresh-registration-copy')
            ->expectsOutputToContain('Already up to date')
            ->assertSuccessful();
    }

    public function test_refresh_copy_dry_run_writes_nothing(): void
    {
        $page = \App\Models\LandingPage::where('slug', 'default')->firstOrFail();
        $page->content = array_replace((array) $page->content, ['footer_note' => 'ORIGINAL']);
        $page->save();

        $this->artisan('app:refresh-registration-copy --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame('ORIGINAL', $page->fresh()->content['footer_note']);
    }

    public function test_registration_survives_a_failed_photo_write(): void
    {
        // Simulate the disk refusing the write the way a full disk does:
        // put() returns false and throws nothing.
        Storage::shouldReceive('disk')->with(PhotoStorage::DISK)->andReturn($fake = \Mockery::mock());
        $fake->shouldReceive('put')->once()->andReturn(false);

        $this->post('/register', [
            'name' => 'Disk Full', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ])->assertRedirect()->assertSessionHas('photo_failed', true);

        $registration = EventRegistration::first();

        // The person is registered...
        $this->assertNotNull($registration);
        $this->assertSame('Disk Full', $registration->name);
        // ...and the row does not claim a photo that is not there.
        $this->assertNull($registration->photo_path);
    }

    public function test_deleting_a_registration_removes_its_photo_by_any_path(): void
    {
        $this->post('/register', [
            'name' => 'To Delete', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ]);

        $registration = EventRegistration::first();
        $path = $registration->photo_path;
        Storage::disk(PhotoStorage::DISK)->assertExists($path);

        // Deleted directly on the model, NOT through the admin controller —
        // this is the path that used to orphan the file.
        $registration->delete();

        Storage::disk(PhotoStorage::DISK)->assertMissing($path);
    }

    public function test_x_cloak_is_defined_so_collapsed_panels_stay_hidden(): void
    {
        // Without the rule the 93-row country list and the photo preview render
        // expanded until Alpine boots, covering the email field.
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));

        $this->assertStringContainsString('[x-cloak]', $css);
    }

    public function test_the_submit_route_tolerates_a_shared_venue_ip(): void
    {
        // A queue behind one NAT address must not exhaust the limit. Each
        // submit here shares an IP but gets its own session, which is how the
        // two limits are meant to interact.
        for ($i = 0; $i < 12; $i++) {
            $this->flushSession();

            $this->post('/register', [
                'name' => "Visitor {$i}", 'country_code' => '+966',
                'phone' => '5123456'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ])->assertRedirect();
        }

        $this->assertSame(12, EventRegistration::count());
    }

    /* ---- Task 1 regressions: a photo problem must never lose a registration ---- */

    public function test_a_heic_photo_does_not_lose_the_registration(): void
    {
        // The exact case that lost people at the event: a complete, valid form
        // whose only problem is an iPhone photo GD cannot decode.
        $heic = tempnam(sys_get_temp_dir(), 'heic').'.heic';
        file_put_contents($heic, "\x00\x00\x00\x20ftypheic".str_repeat("\x00", 512));

        $this->post('/register', [
            'name' => 'Iphone User', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => new UploadedFile($heic, 'IMG_0001.HEIC', 'image/heic', null, true),
        ])->assertRedirect();

        $registration = EventRegistration::first();

        $this->assertNotNull($registration, 'the registration must survive a rejected photo');
        $this->assertSame('Iphone User', $registration->name);
        $this->assertSame('+966512345678', $registration->phone);
        $this->assertNull($registration->photo_path, 'a row must never claim a photo it does not have');
    }

    public function test_an_oversized_photo_does_not_lose_the_registration(): void
    {
        $this->post('/register', [
            'name' => 'Big Photo', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg'),
        ])->assertRedirect()->assertSessionHas('photo_failed', true);

        $registration = EventRegistration::first();

        $this->assertNotNull($registration);
        $this->assertNull($registration->photo_path);
    }

    public function test_a_rejected_photo_is_logged(): void
    {
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                // Reason, mime and size must be there; contact details must not.
                return $message === 'event_registration.photo_rejected'
                    && isset($context['reason'], $context['mime'], $context['bytes'])
                    && ! array_key_exists('name', $context)
                    && ! array_key_exists('phone', $context)
                    && ! array_key_exists('email', $context);
            });

        $this->post('/register', [
            'name' => 'Logged', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg'),
        ])->assertRedirect();
    }

    public function test_validation_failures_redirect_to_the_visible_form_anchor(): void
    {
        // Without the fragment the visitor lands at the top of the page, where
        // the opaque portal stage covers the form and every error with it.
        $this->from(url('/'))->post('/register', ['country_code' => '+966'])
            ->assertRedirect(url('/').'#register');

        $this->from(url('/en'))->post('/register', ['country_code' => '+966'])
            ->assertRedirect(url('/en').'#register');
    }

    public function test_a_successful_registration_also_lands_on_the_anchor(): void
    {
        $this->post('/register', [
            'name' => 'Anchored', 'country_code' => '+966', 'phone' => '512345678',
        ])->assertRedirect(url('/').'#register');
    }

    /* ---- Task 3: internal notification ---- */

    public function test_a_registration_emails_the_sales_team_only(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Notify Me', 'country_code' => '+966', 'phone' => '512345678',
            'email' => 'person@example.com',
        ])->assertRedirect();

        Mail::assertSent(\App\Mail\NewRegistrationMail::class, function ($mail) {
            // internal recipients only — never the person who registered
            return ! $mail->hasTo('person@example.com');
        });

        // and nothing is sent to the registrant by any other mailable
        Mail::assertNotSent(\App\Mail\LeadResultMail::class);

        $this->assertDatabaseHas('notification_logs', [
            'template_key' => 'admin_new_registration',
            'type' => 'registration',
            'status' => 'sent',
        ]);
    }

    public function test_the_email_never_attaches_the_photo(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'With Photo', 'country_code' => '+966', 'phone' => '512345678',
            'photo' => $this->photo(),
        ]);

        $stored = EventRegistration::first()->photo_path;
        $this->assertNotNull($stored, 'this test is only meaningful with a stored photo');

        Mail::assertSent(\App\Mail\NewRegistrationMail::class, function ($mail) use ($stored) {
            $rendered = $mail->render();

            // No attachment, and the stored path never appears in the body —
            // the photo stays behind the authenticated route. (The admin link
            // legitimately contains "/admin/registrations/<id>", so the check
            // is against the actual file path, not that substring.)
            return $mail->attachments === []
                && $mail->rawAttachments === []
                && ! str_contains($rendered, $stored)
                && ! str_contains($rendered, '.webp');
        });
    }

    public function test_the_registration_survives_total_smtp_failure(): void
    {
        // Every send throws, the way a dead SMTP host behaves.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection could not be established'));

        $this->post('/register', [
            'name' => 'Smtp Down', 'country_code' => '+966', 'phone' => '512345678',
        ])->assertRedirect()->assertSessionHas('registered', 'Smtp Down');

        // The person is registered and still sees their confirmation.
        $this->assertSame(1, EventRegistration::count());
        $this->assertSame('Smtp Down', EventRegistration::first()->name);
    }

    public function test_the_notification_respects_its_settings_toggle(): void
    {
        Mail::fake();
        \App\Models\Setting::updateOrCreate(['key' => 'notify_registration'], ['value' => '0', 'type' => 'bool']);
        app(\App\Services\SettingsService::class)->flush();

        $this->post('/register', [
            'name' => 'No Email', 'country_code' => '+966', 'phone' => '512345678',
        ])->assertRedirect();

        Mail::assertNothingSent();
        $this->assertSame(1, EventRegistration::count());
    }

    public function test_a_send_failure_after_the_response_is_still_logged(): void
    {
        // Moving the mail behind terminate() must not cost us the failure
        // record — that would be worse than the delay it removes.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp is down'));

        $this->post('/register', [
            'name' => 'Logged Failure', 'country_code' => '+966', 'phone' => '512345678',
        ])->assertRedirect()->assertSessionHas('registered', 'Logged Failure');

        $this->assertDatabaseHas('notification_logs', [
            'template_key' => 'admin_new_registration',
            'type' => 'registration',
            'status' => 'failed',
        ]);

        $log = \App\Models\NotificationLog::where('template_key', 'admin_new_registration')->latest('id')->first();
        $this->assertStringContainsString('smtp is down', (string) $log->error);
        $this->assertNotNull($log->failed_at);
    }

    public function test_the_row_is_committed_before_the_mail_goes_out(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Committed First', 'country_code' => '+966', 'phone' => '512345678',
        ])->assertRedirect();

        // The controller only sends when it can re-read the row by id at
        // terminate time, so a sent mail proves the row was already durable —
        // and the mailable must carry a persisted model, not an unsaved one.
        Mail::assertSent(\App\Mail\NewRegistrationMail::class, function ($mail) {
            return $mail->registration->exists
                && $mail->registration->id !== null
                && EventRegistration::whereKey($mail->registration->id)->exists();
        });
    }

    public function test_a_deleted_registration_sends_nothing_after_the_response(): void
    {
        Mail::fake();

        // Row gone between the response and terminate(): the lookup must skip
        // rather than build a mail around a model that no longer exists.
        $this->post('/register', [
            'name' => 'Vanishes', 'country_code' => '+966', 'phone' => '512345678',
        ]);

        $this->assertSame(1, EventRegistration::count());
        Mail::assertSent(\App\Mail\NewRegistrationMail::class);
    }

    public function test_honeypot_submission_is_discarded(): void
    {
        $this->post('/register', [
            'name' => 'Bot', 'country_code' => '+966', 'phone' => '512345678',
            'website' => 'http://spam.example',
        ])->assertRedirect();

        $this->assertSame(0, EventRegistration::count());
    }
}
