<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected MediaService $media) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'backdrop' => $this->media->slot('auth'),
            'googleEnabled' => $this->googleEnabled(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'محاولات كتير. جرب تاني بعد '.RateLimiter::availableIn($key).' ثانية.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'الحساب ده متوقف.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public static function googleEnabled(): bool
    {
        return str_contains((string) config('creativemark.auth.providers'), 'google')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
