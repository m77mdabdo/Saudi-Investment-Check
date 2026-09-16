<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GoogleAuthController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\QrSourceController;
use App\Http\Controllers\Admin\QuizBuilderController;
use App\Http\Controllers\Admin\ResultRuleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public journey — QR ▸ landing ▸ quiz ▸ lead ▸ result
|--------------------------------------------------------------------------
| Arabic (the default locale) keeps the original un-prefixed URLs so every
| QR code and indexed link already in the wild keeps working. English is
| served from /en/… so each language has its own canonical URL for hreflang.
*/

$publicRoutes = function () {
    Route::get('/', LandingController::class)->name('landing');
    Route::get('/quiz', [QuizController::class, 'show'])->name('quiz');
    Route::get('/result/{lead}', [QuizController::class, 'result'])->name('result');
};

// English
Route::prefix('en')->name('en.')->group($publicRoutes);

// Arabic (default — no prefix)
Route::group([], $publicRoutes);

// /ar/... is accepted as an alias and redirects to the canonical Arabic URL.
Route::get('/ar/{path?}', function (?string $path = null) {
    return redirect('/'.ltrim((string) $path, '/'), 301);
})->where('path', '.*')->name('ar.alias');

// Locale-agnostic endpoints (no SEO surface): the language comes from the
// session/referer via the SetLocale middleware.
Route::post('/quiz/state', [QuizController::class, 'sync'])->middleware('throttle:quiz-state')->name('quiz.sync');
Route::post('/quiz/progress', [QuizController::class, 'progress'])->middleware('throttle:quiz-state')->name('quiz.progress');
Route::post('/quiz/completed', [QuizController::class, 'completed'])->middleware('throttle:quiz-state')->name('quiz.completed');
Route::post('/quiz/submit', [QuizController::class, 'submit'])->middleware('throttle:quiz-submit')->name('quiz.submit');
Route::post('/track', [QuizController::class, 'track'])->middleware('throttle:tracking')->name('track');

// Language switcher: /language/en?redirect=/en/quiz
Route::get('/language/{locale}', LanguageController::class)
    ->where('locale', implode('|', \App\Support\Locale::supported()))
    ->name('language.switch');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Short QR entry points: /qr/booth_qr → landing with attribution preserved.
Route::get('/qr/{slug}', function (string $slug) {
    return redirect()->to(lroute('landing', ['source' => $slug]));
})->where('slug', '[a-z0-9_\-]+')->name('qr.entry');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:admin-login')->name('login.attempt');
        Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    });

    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'admin.active'])->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        // Leads — available to every signed-in staff member.
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/export', [LeadController::class, 'export'])->name('leads.export');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
        Route::patch('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
        Route::post('leads/{lead}/notes', [LeadController::class, 'storeNote'])->name('leads.notes.store');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        Route::get('analytics', AnalyticsController::class)->name('analytics');

        // Reading the log is operational; sending mail from it is not.
        Route::get('emails', [EmailLogController::class, 'index'])->name('emails.index');
        Route::post('emails/test', [EmailLogController::class, 'test'])
            ->middleware(['admin.manage', 'throttle:10,1'])->name('emails.test');
        Route::post('emails/{log}/resend', [EmailLogController::class, 'resend'])
            ->middleware(['admin.manage', 'throttle:20,1'])->name('emails.resend');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        // Platform configuration — managers and admins only.
        Route::middleware('admin.manage')->group(function () {
            Route::get('quiz', [QuizBuilderController::class, 'index'])->name('quiz.index');
            Route::get('quiz/create', [QuizBuilderController::class, 'create'])->name('quiz.create');
            Route::post('quiz', [QuizBuilderController::class, 'store'])->name('quiz.store');
            Route::post('quiz/reorder', [QuizBuilderController::class, 'reorder'])->name('quiz.reorder');
            Route::get('quiz/{question}/edit', [QuizBuilderController::class, 'edit'])->name('quiz.edit');
            Route::put('quiz/{question}', [QuizBuilderController::class, 'update'])->name('quiz.update');
            Route::patch('quiz/{question}/toggle', [QuizBuilderController::class, 'toggle'])->name('quiz.toggle');
            Route::delete('quiz/{question}', [QuizBuilderController::class, 'destroy'])->name('quiz.destroy');
            Route::post('quiz/{question}/options', [QuizBuilderController::class, 'storeOption'])->name('quiz.options.store');
            Route::post('quiz/{question}/options/reorder', [QuizBuilderController::class, 'reorderOptions'])->name('quiz.options.reorder');
            Route::put('quiz/{question}/options/{option}', [QuizBuilderController::class, 'updateOption'])->name('quiz.options.update');
            Route::delete('quiz/{question}/options/{option}', [QuizBuilderController::class, 'destroyOption'])->name('quiz.options.destroy');

            Route::get('results', [ResultRuleController::class, 'index'])->name('results.index');
            Route::get('results/create', [ResultRuleController::class, 'create'])->name('results.create');
            Route::post('results', [ResultRuleController::class, 'store'])->name('results.store');
            Route::get('results/{result}/edit', [ResultRuleController::class, 'edit'])->name('results.edit');
            Route::put('results/{result}', [ResultRuleController::class, 'update'])->name('results.update');
            Route::delete('results/{result}', [ResultRuleController::class, 'destroy'])->name('results.destroy');

            Route::get('events', [EventController::class, 'index'])->name('events.index');
            Route::get('events/create', [EventController::class, 'create'])->name('events.create');
            Route::post('events', [EventController::class, 'store'])->name('events.store');
            Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
            Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
            Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

            Route::get('qr-sources', [QrSourceController::class, 'index'])->name('qr.index');
            Route::post('qr-sources', [QrSourceController::class, 'store'])->name('qr.store');
            Route::put('qr-sources/{qr}', [QrSourceController::class, 'update'])->name('qr.update');
            Route::patch('qr-sources/{qr}/toggle', [QrSourceController::class, 'toggle'])->name('qr.toggle');
            Route::delete('qr-sources/{qr}', [QrSourceController::class, 'destroy'])->name('qr.destroy');

            Route::get('cms', [CmsController::class, 'edit'])->name('cms.edit');
            Route::put('cms', [CmsController::class, 'update'])->name('cms.update');

            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::post('media/pin', [MediaController::class, 'pin'])->name('media.pin');
            Route::post('media/refresh', [MediaController::class, 'refresh'])->name('media.refresh');
            Route::post('media/clear', [MediaController::class, 'clear'])->name('media.clear');

            Route::get('notification-templates', [NotificationController::class, 'templates'])->name('notifications.templates');
            Route::put('notification-templates/{template}', [NotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
            Route::post('notification-templates/{template}/preview', [NotificationController::class, 'previewTemplate'])->name('notifications.templates.preview');

            Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::post('settings/statuses', [SettingsController::class, 'storeStatus'])->name('settings.statuses.store');
            Route::put('settings/statuses/{status}', [SettingsController::class, 'updateStatus'])->name('settings.statuses.update');
            Route::delete('settings/statuses/{status}', [SettingsController::class, 'destroyStatus'])->name('settings.statuses.destroy');

            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

// Google OAuth callback path is configured in the environment (GOOGLE_CALLBACK_URL).
Route::get(parse_url((string) config('services.google.redirect'), PHP_URL_PATH) ?: '/auth/google/callback',
    [GoogleAuthController::class, 'callback'])->name('admin.google.callback');
