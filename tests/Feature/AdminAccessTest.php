<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/leads')->assertRedirect(route('admin.login'));
        $this->get('/admin/settings')->assertRedirect(route('admin.login'));
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('تسجيل دخول الفريق', false);
    }

    public function test_staff_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'team@creativemark.test', 'password' => Hash::make('Secret-Pass-2026'), 'role' => 'admin']);

        $this->post('/admin/login', ['email' => 'team@creativemark.test', 'password' => 'Secret-Pass-2026'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_bad_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'team@creativemark.test', 'password' => Hash::make('Secret-Pass-2026')]);

        $this->post('/admin/login', ['email' => 'team@creativemark.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'old@creativemark.test', 'password' => Hash::make('Secret-Pass-2026'), 'is_active' => false]);

        $this->post('/admin/login', ['email' => 'old@creativemark.test', 'password' => 'Secret-Pass-2026'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        User::factory()->create(['email' => 'team@creativemark.test', 'password' => Hash::make('Secret-Pass-2026')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'team@creativemark.test', 'password' => 'wrong']);
        }

        $response = $this->post('/admin/login', ['email' => 'team@creativemark.test', 'password' => 'Secret-Pass-2026']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_sales_users_cannot_reach_configuration_pages(): void
    {
        $this->actingAs($this->admin('sales'));

        $this->get('/admin')->assertOk();
        $this->get('/admin/leads')->assertOk();

        foreach (['/admin/quiz', '/admin/results', '/admin/settings', '/admin/cms', '/admin/media', '/admin/events', '/admin/qr-sources', '/admin/users'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_only_admins_manage_users(): void
    {
        $this->actingAs($this->admin('manager'));
        $this->get('/admin/users')->assertForbidden();

        $this->actingAs($this->admin('admin'));
        $this->get('/admin/users')->assertOk();
    }

    public function test_signing_out_works(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
