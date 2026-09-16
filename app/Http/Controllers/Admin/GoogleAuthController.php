<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * Optional Google sign-in for staff only. Public quiz users never authenticate.
 * Credentials are read from the environment via config/services.php.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(AuthController::googleEnabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(AuthController::googleEnabled(), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('oauth.google_failed', ['error' => $e->getMessage()]);

            return redirect()->route('admin.login')->withErrors(['email' => 'تعذر تسجيل الدخول عبر Google.']);
        }

        $email = strtolower((string) $googleUser->getEmail());

        if (! $email) {
            return redirect()->route('admin.login')->withErrors(['email' => 'حساب Google بدون بريد إلكتروني.']);
        }

        if (! $this->domainAllowed($email)) {
            return redirect()->route('admin.login')->withErrors(['email' => 'النطاق ده غير مسموح بالدخول.']);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            if (! config('creativemark.auth.google_auto_register')) {
                return redirect()->route('admin.login')->withErrors([
                    'email' => 'مفيش حساب بالبريد ده. اطلب من الأدمن إنشاء حساب ليك.',
                ]);
            }

            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Str::password(32),
                'role' => 'sales',
                'is_active' => true,
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('admin.login')->withErrors(['email' => 'الحساب ده متوقف.']);
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, true);
        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    protected function domainAllowed(string $email): bool
    {
        $allowed = trim((string) config('creativemark.auth.google_allowed_domains'));

        if ($allowed === '') {
            return true;
        }

        $domain = Str::after($email, '@');

        return collect(preg_split('/[,;\s]+/', $allowed))
            ->filter()
            ->map(fn ($d) => strtolower(ltrim($d, '@')))
            ->contains($domain);
    }
}
