<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Event;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        // Relative timestamps ("منذ 3 دقائق") read naturally in the RTL UI.
        \Carbon\Carbon::setLocale('ar');
        CarbonImmutable::setLocale('ar');

        /*
         | Laravel's default throttle signature is just domain|IP, so every
         | throttled POST would share one bucket — the quiz's own autosave
         | calls would then lock the visitor out of submitting. Each endpoint
         | gets its own named limiter instead.
         */
        RateLimiter::for('quiz-submit', fn (Request $request) => Limit::perMinute(10)->by('quiz-submit|'.$request->ip()));
        RateLimiter::for('quiz-state', fn (Request $request) => Limit::perMinute(180)->by('quiz-state|'.$request->ip()));
        RateLimiter::for('tracking', fn (Request $request) => Limit::perMinute(120)->by('tracking|'.$request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(10)
            ->by('admin-login|'.$request->ip().'|'.strtolower((string) $request->input('email'))));

        // Shared chrome for every admin screen.
        View::composer(['components.layouts.admin', 'admin.*'], function ($view) {
            $view->with([
                'unreadNotifications' => AdminNotification::query()->whereNull('read_at')->count(),
                'latestNotifications' => AdminNotification::query()->latest()->limit(6)->get(),
                'currentEvent' => Event::current(),
                'allEvents' => Event::query()->orderByDesc('is_default')->get(),
            ]);
        });
    }
}
