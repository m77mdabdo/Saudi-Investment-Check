<?php

/*
|--------------------------------------------------------------------------
| Creative Mark — Saudi Market Readiness Platform
|--------------------------------------------------------------------------
| Defaults only. Anything here can be overridden at runtime from
| Admin → Settings (settings table, cached). Never hard-code secrets.
*/

return [

    /*
    | Supported locales. `base_locale` is the language the editable database
    | content (quiz, results, CMS) is written in; everything else lives in a
    | `translations` JSON column next to it.
    */
    'base_locale' => 'ar',

    // When true, a first-time visitor's Accept-Language header picks the
    // language. Off by default so everyone lands on the Arabic experience.
    'auto_detect_locale' => (bool) env('LOCALE_AUTO_DETECT', false),

    'locales' => [
        'ar' => ['name' => 'العربية', 'native' => 'العربية', 'dir' => 'rtl', 'flag' => '🇸🇦', 'html' => 'ar', 'iso' => 'ar_SA'],
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'flag' => '🇬🇧', 'html' => 'en', 'iso' => 'en_GB'],
    ],

    'brand' => [
        'name' => env('BRAND_NAME', 'Creative Mark'),
        'tagline' => env('BRAND_TAGLINE', 'Creating The Future'),
        'logo' => env('BRAND_LOGO', 'images/creative-mark-logo.png'),
    ],

    // Public CTA destinations — configurable, never invented at runtime.
    'cta' => [
        'whatsapp_url' => env('CREATIVE_MARK_WHATSAPP_URL', ''),
        'booking_url' => env('BOOKING_URL', ''),
        'checklist_url' => env('CHECKLIST_URL', ''),
        'phone' => env('CREATIVE_MARK_PHONE', ''),
        'email' => env('CREATIVE_MARK_EMAIL', ''),
        'website' => env('CREATIVE_MARK_WEBSITE', ''),
    ],

    'quiz' => [
        'max_score' => 12,
        'session_key' => 'smrc.quiz',
        'result_token_ttl_days' => 30,
    ],

    'notifications' => [
        'admin_recipients' => env('LEAD_NOTIFY_EMAILS', ''),
        'notify_admin' => (bool) env('LEAD_NOTIFY_ADMIN', true),
        'notify_customer' => (bool) env('LEAD_NOTIFY_CUSTOMER', true),
    ],

    'auth' => [
        // Comma separated list, e.g. "google". Empty hides social sign-in.
        'providers' => env('OAUTH_PROVIDERS', ''),

        // Comma separated list of email domains allowed to sign in with Google.
        'google_allowed_domains' => env('GOOGLE_ALLOWED_DOMAINS', ''),
        // Only pre-existing users may sign in via Google unless enabled.
        'google_auto_register' => (bool) env('GOOGLE_AUTO_REGISTER', false),
    ],

    'media' => [
        // Local fallbacks used whenever the Pexels API is unavailable.
        'fallbacks' => [
            'hero' => 'images/fallback/hero.svg',
            'result' => 'images/fallback/result.svg',
            'auth' => 'images/fallback/auth.svg',
            'event' => 'images/fallback/event.svg',
            'empty' => 'images/fallback/empty.svg',
        ],
        'queries' => [
            'hero' => 'riyadh skyline modern architecture',
            'result' => 'saudi business meeting modern office',
            'auth' => 'modern office building night city',
            'event' => 'business conference audience stage',
            'empty' => 'abstract minimal gradient architecture',
        ],
    ],
];
