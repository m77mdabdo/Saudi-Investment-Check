# Saudi-Ready Check — Creative Mark 🇸🇦

A mobile-first **Saudi market readiness assessment + lead generation + mini CRM** built for
Creative Mark and the **TECHNE — Alexandria 2026** event.

A visitor scans a QR code, answers 8 tap-only questions in under a minute, leaves their
contact details, and gets a scored readiness result. The sales team sees the lead — with its
source, score, classification and answers — in the console immediately.

```
QR ▸ Landing ▸ 8 questions ▸ Lead form ▸ Server-side score ▸ Result + CTA
                                                   │
                                     Email alert + dashboard notification
```

---

## Stack

| Layer | Choice |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Frontend | Blade + Tailwind CSS 4 + Alpine.js, built with Vite |
| Charts | Chart.js (admin only) |
| Database | MySQL 8 (SQLite in-memory for tests) |
| Mail | Laravel Mail over SMTP |
| Auth | Session auth + optional Google OAuth (Socialite), staff only |
| Export | Laravel Excel (`maatwebsite/excel`) |
| Imagery | Pexels API, cached in the database, with bundled local fallbacks |

---

## Quick start

```bash
composer install
npm install
cp .env.example .env            # then fill in your own values
php artisan key:generate

php artisan migrate --seed      # prints the generated admin password once
php artisan storage:link        # event logos are stored on the public disk
php artisan media:sync          # optional: pull background imagery from Pexels

npm run build                   # or: npm run dev
php artisan serve
```

* Public journey → `http://localhost:8000`
* Staff console → `http://localhost:8000/admin`

### Admin account

`db:seed` creates the first administrator from `ADMIN_EMAIL` / `ADMIN_NAME`.
If `ADMIN_PASSWORD` is empty, a strong password is generated and **printed once** in the
seeder output — store it in a password manager and rotate it after first login.
More users are created in **Admin → Users & Roles**.

Roles: `admin` (everything), `manager` (content + leads), `sales` (leads only).

---

## Environment

Every integration degrades gracefully when its variables are absent.

| Group | Variables | Notes |
| --- | --- | --- |
| App | `APP_*`, `DB_*`, `SESSION_*`, `CACHE_STORE`, `QUEUE_CONNECTION` | Standard Laravel |
| Mail | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Lead + customer emails |
| Pexels | `PEXELS_API_KEY`, `PEXELS_CACHE_TTL`, `PEXELS_TIMEOUT` | Missing key → bundled fallback images |
| Google OAuth | `OAUTH_PROVIDERS`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_CALLBACK_URL`, `GOOGLE_ALLOWED_DOMAINS`, `GOOGLE_AUTO_REGISTER` | Staff sign-in only; hidden when unset |
| CTA links | `CREATIVE_MARK_WHATSAPP_URL`, `BOOKING_URL`, `CHECKLIST_URL`, `CREATIVE_MARK_PHONE`, `CREATIVE_MARK_EMAIL`, `CREATIVE_MARK_WEBSITE` | Defaults; Admin → Settings overrides them |
| Notifications | `LEAD_NOTIFY_ADMIN`, `LEAD_NOTIFY_CUSTOMER`, `LEAD_NOTIFY_EMAILS` | Also editable in Settings |
| Branding | `BRAND_NAME`, `BRAND_TAGLINE`, `BRAND_LOGO` | Drop the logo at `public/images/creative-mark-logo.png` |
| Hosting | `TRUSTED_PROXIES` | Set to your load balancer's IPs (or `*`) only when the app sits behind one |

**Secrets live in `.env` only.** They are read through `config/services.php`,
`config/mail.php` and `config/creativemark.php`, never hard-coded, never sent to the browser,
and `.env` is git-ignored.

---

## How scoring works

Six of the eight questions are scored 0/1/2 → **maximum 12 points**. Scoring always happens
server-side in `App\Services\ScoringService`: the browser only ever submits *option keys*,
which are validated against the live quiz before any points are read from the database.

| Score | Result | Classification | Tone |
| --- | --- | --- | --- |
| 9–12 | READY | Hot Lead | Green |
| 5–8 | NEEDS PREP | Warm Lead | Amber |
| 0–4 | EARLY STAGE | Early Lead | Coral |

Bands, copy, bullets and CTAs are rows in `result_rules` — edit them in **Admin → Results**,
no deployment required.

---

## Admin console

| Area | What you can do |
| --- | --- |
| **Dashboard** | Overview KPIs, funnel, leads trend, classification, sources, sectors, recent leads, hot-lead follow-up queue |
| **Leads** | CRM list with filters (date, event, result, status, source, sector, timeline, owner) + search, inline status changes, WhatsApp/call/email actions, Excel export |
| **Lead detail** | Contact, answers, score, attribution (source/UTM/device), sales status, assignment, internal notes, activity timeline, email log |
| **Analytics** | Visits → starts → completions → leads → meetings, event counters, devices, sources, sectors |
| **Quiz** | Build questions and options, scores, "other" free-text options, ordering, activate/deactivate, live preview |
| **Results** | Score bands, headlines, body, bullets, highlight, CTA labels and URLs, disclaimer; warns about uncovered score gaps |
| **CMS** | Every public string: hero, description, benefit cards, quiz intro, lead form copy, consent text, footer, SEO |
| **Media** | Search Pexels per slot, pin an image, auto-refresh, or fall back to the bundled artwork |
| **Events** | Multiple events, default event, dates, status; every lead belongs to one |
| **QR Sources** | Create tracked sources, copy their URL, see leads and hot leads per source |
| **Email templates** | Subject/body with `{{name}}`, `{{company}}`, `{{score}}`, `{{result}}`, `{{classification}}`, `{{source}}`, `{{event}}`, `{{main_question}}`, `{{cta_url}}`… plus send-preview and logs |
| **Settings** | CTA links, notification recipients and toggles, sales statuses, integration health |
| **Users & Roles** | Create staff, change roles, deactivate accounts |

### QR sources

Each source has a slug; both of these carry attribution through the whole journey and land
on the lead record:

```
https://your-domain/?source=booth_qr
https://your-domain/qr/booth_qr
```

Seeded: `walking_qr`, `booth_qr`, `portfolio_qr`, `vip_qr`, `partner_qr`.

---

## Data model

`users`, `events`, `landing_pages`, `quiz_questions`, `quiz_options`, `result_rules`,
`sales_statuses`, `leads`, `lead_answers`, `lead_notes`, `analytics_events`, `qr_sources`,
`notification_templates`, `notification_logs`, `admin_notifications`, `settings`,
`media_assets` — all created by `database/migrations/2026_01_01_000100_create_platform_core_tables.php`
with foreign keys and indexes on the columns the dashboard filters by.

---

## Security

* Server-side scoring; option keys validated against the live quiz
* CSRF on every form, rate limiting per endpoint (submit, autosave, tracking, login)
* Honeypot field + one-lead-per-session duplicate protection
* Result pages open only for the session that created them, or via a signed URL
* Role-based authorization on every admin route, login throttling, deactivation support
* IP addresses stored only as a keyed hash; no secrets in HTML, JS or logs
* Set `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true` in production

---

## Tests

```bash
php artisan test
```

71 tests covering the public journey, scoring boundaries (12/9/8/5/4/0), validation,
duplicate submissions, QR/UTM attribution, analytics, notifications, media fallbacks,
security, authorization, and every admin workflow.

---

## Deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan media:sync          # optional
```

Point the web root at `public/`, set `APP_ENV=production`, `APP_DEBUG=false`, and run
`php artisan queue:work` if you switch mail to a queued connection.
After changing `.env` on a cached deployment, re-run `php artisan config:cache`.
