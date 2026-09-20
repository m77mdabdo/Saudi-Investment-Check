# Code Audit — Saudi-Ready Check

**Date:** 2026-09-17 · **Scope:** full codebase, read-only · **Test suite:** 137 passed / 637 assertions
**Stack:** Laravel 12, PHP 8.5, MySQL, Hostinger (domain served from the project root via a root `.htaccess`)

---

## Summary

The core lead workflow is sound: validation, lead creation and scoring run inside one transaction, email sending happens after it in isolated steps, and a mail failure can no longer lose a lead or produce a 500 — this is verified by tests and by live SMTP runs.
Security is in good shape: all 66 admin routes are authenticated, no mass assignment from raw request input, no raw SQL built from user input, CSRF is applied everywhere, and no credential has ever been committed to git.
The two problems that will bite you with real customers are **phone normalisation, which can silently store an unreachable number**, and **rate limiting keyed only by IP, which will throttle a whole venue sharing one Wi-Fi connection** — exactly the TECHNE scenario.
Synchronous mail is the right call at this volume, but the worst case is a ~60-second wait before the visitor sees their result, and that exceeds typical shared-hosting execution limits.
One safeguard I added in the test suite is defective: it reports a wrong-database test run *after* `RefreshDatabase` has already wiped that database, instead of preventing it.

---

## Critical

Nothing in this tier. No data-loss path, no authentication bypass, no exposed secret, no injection vector was found.

---

## High

### H1 — A lead can be saved with an unreachable WhatsApp number
**`app/Http/Requests/LeadSubmissionRequest.php:24` (rule), `:46-57` (normalisation)**

The rule is `min:6|max:20|regex:/^[0-9\s\-\(\)]+$/`, measured against the raw string *including* spaces, dashes and brackets. `whatsapp()` then strips non-digits and strips leading zeros. Verified outputs:

| input | stored value |
| --- | --- |
| `+20` / `000000` | `+20` |
| `+20` / `00000000000` | `+20` |
| `+966` / `5 0 1 2 3 4` | `+966501234` |

**Impact:** WhatsApp is the primary sales channel and the only mandatory contact field. A lead stored as `+20` is unreachable, and `Lead::whatsappLink()` (`app/Models/Lead.php`) produces `https://wa.me/20`, a dead link the sales team will click. There is no server-side check that a usable subscriber number survives normalisation. At an event this shows up as leads that look complete in the dashboard and cannot be contacted.

**Fix direction:** validate the normalised value, not the raw input — require 7–15 digits after the country code (E.164), and reject when the subscriber part is empty.

### H2 — Rate limits are keyed by IP, and an event venue is one IP
**`app/Providers/AppServiceProvider.php:45-47`**

```php
'quiz-submit' → 10 / minute per IP
'quiz-state'  → 180 / minute per IP
'tracking'    → 120 / minute per IP
```

Every attendee on the venue Wi-Fi shares one public IP. Eleven people submitting in the same minute means the eleventh gets **HTTP 429 instead of a lead**. The autosave endpoint is worse: each visitor fires roughly 20 calls while answering, so about nine concurrent visitors exhaust `quiz-state`.

**Impact:** direct, silent lead loss during exactly the moment the product exists for — a busy booth. The submit throttle is the money path; the 429 page is not the friendly result screen.

**Fix direction:** key the limiter by session id (falling back to IP) rather than IP alone, and raise the submit ceiling. Keep a per-IP ceiling as a second, much higher limiter for abuse.

### H3 — Test-database guard fires after the damage is done
**`tests/TestCase.php:21-33`**

The guard checks the connection in `setUp()` — but `parent::setUp()` on line 23 is what runs `RefreshDatabase`, which drops and re-migrates the database. When a cached config points the suite at MySQL, the wipe happens first and the guard only reports it. This is not theoretical: it destroyed the local development database twice during this project, most recently during this audit session.

**Impact:** any developer (or CI job) running `php artisan test` with a stale `bootstrap/cache/config.php` loses whatever database that config points to. If that config ever points at a staging or production host, the loss is not local.

**Fix direction:** move the assertion into `beforeRefreshingDatabase()` (the hook exists on `RefreshDatabase`), so it aborts before any schema command runs.

---

## Medium

### M1 — Worst-case wait before the result page is ~60 seconds
**`app/Services/NotificationService.php:186-217`, `config/mail.php:62` (`MAIL_TIMEOUT=30`)**

Two SMTP conversations run inside the submit request (sales + customer). Measured live: **~5 s** for both against Gmail. But each send can block up to `MAIL_TIMEOUT`, so a silently dropped connection costs 30 s per email — about **60 s** before the redirect. Shared hosting typically caps PHP execution at 30–60 s, so the request can die *after* the lead is saved; the lead survives (it is committed first), but the visitor sees an error page instead of their result.

**Recommendation — keep it synchronous.** A queue would mean Supervisor or a cron worker on Hostinger, which is the fragility you explicitly wanted to avoid, and the current design already guarantees the lead is safe. Instead, bound the blast radius: set `MAIL_TIMEOUT=8`, which keeps the worst case at ~16 s and still comfortably covers Gmail's normal 2–3 s response. Revisit queueing only if submission volume makes 5 s per request a throughput problem.

### M2 — `MAIL_MAILER=log` records deliveries as "sent"
**`app/Services/NotificationService.php:187-189`**

`Mail::to()->send()` succeeds with the `log` mailer, so the row is written as `sent` even though nothing left the server. Production ran in exactly that state for a period. The `mailer` column records `log`, so the truth is recoverable, but the dashboard and the status word say the opposite.

**Impact:** false confidence — the team believes customers were emailed when they were not.

**Fix direction:** in `log()`, record status `simulated` (or flag the row) whenever the mailer is `log`/`array`, and surface it in the Email Logs badge.

### M3 — A score with no matching result rule is stored silently
**`app/Models/ResultRule.php:29-37`, consumed at `app/Services/LeadService.php:46-48`**

`forScore()` returns `null` when no active rule covers the score. The lead is then written with `result_key`, `classification` and `result_rule_id` all null. Nothing logs it, nothing alerts. Current data is fine — coverage is complete for 0–12 — but the bands are admin-editable, and adding a scored question raises the maximum without touching the rules.

**Impact:** such leads disappear from every dashboard breakdown (ready/needs-prep/early), the customer email loses its headline, and the sales subject line renders an empty classification. The admin Results screen does warn about gaps, but only if someone looks.

**Fix direction:** log a warning when `forScore()` misses, and fall back to the nearest band rather than storing null.

### M4 — The quiz definition is queried three times per submission
**`app/Services/ScoringService.php:24, 43, 112`**

Measured on a real submission: `select * from quiz_questions where is_active = ?` ran **3×**, each with its `activeOptions` eager load — once for `validationRules()`, once for `evaluate()`, once for `maxScore()`. Roughly six queries where two would do.

**Impact:** small today (8 questions, ~9 queries in the measured path), but it is on the hot path and grows with every question added. Not urgent; easy to fix by memoising `questions()` on the service instance.

### M5 — No audit trail for admin sign-ins or lead exports
**`app/Http/Controllers/Admin/AuthController.php`, `app/Http/Controllers/Admin/LeadController.php` (`export`)**

Neither successful nor failed logins are written to the application log (only the rate limiter reacts), and exporting the full lead list — name, WhatsApp, email, answers — leaves no trace of who did it or when.

**Impact:** with real customer data under consent, you cannot answer "who pulled this list?" or spot credential-stuffing attempts. This is the kind of gap that matters the day something goes wrong.

### M6 — The email table component trusts its callers with raw HTML
**`resources/views/components/mail/data-table.blade.php:16`**

`{!! $value !!}` renders values unescaped, by design, so links can be embedded. Every current caller escapes properly (`NewLeadMail` wraps lead fields in `e()`), so there is no live XSS. But the contract is implicit: the first caller that forgets `e()` injects lead-controlled HTML into an email that staff open.

**Fix direction:** accept an explicit `raw` flag per row, escape by default.

---

## Low

### L1 — `log()` inside the failure handler can itself throw
**`app/Services/NotificationService.php:200`**

If the `notification_logs` insert fails (missing column on a half-migrated server, DB unavailable), the exception escapes the `catch`. It is caught upstream by `safely()`, so no 500 and no lost lead — but that delivery failure is then recorded only in `laravel.log`, not in the table the admin screen reads.

### L2 — `result_rules` has no index on `min_score` / `max_score`
**`database/migrations/2026_01_01_000100_create_platform_core_tables.php:89-90`**

`forScore()` filters on both columns. The table holds three rows, so this is currently irrelevant — noted for completeness only.

### L3 — Dead files
`resources/views/welcome.blade.php` (no route references it) and `resources/js/bootstrap.js` (imported by nothing, not a Vite entry point). Both are Laravel scaffolding left over from the initial install.

### L4 — Lead search cannot use an index
**`app/Models/Lead.php` (`scopeSearch`)**

Name/company/WhatsApp/email search uses `LIKE '%term%'`. Leading wildcards cannot use an index; at tens of thousands of leads this becomes a full scan. Fine at event scale, worth knowing before a large import.

### L5 — No data-retention policy
Leads, answers, IP hashes and analytics rows are kept indefinitely. Consent is recorded (`consent`, `consent_at`) and IPs are stored only as HMAC hashes, which is good, but there is no expiry or purge command.

---

## What is solid

These were checked and found correct — no action needed.

* **Transaction boundary.** `app/Services/LeadService.php:33-96` wraps the lead and all answers in one transaction; scoring runs before it; notifications are triggered afterwards from `app/Http/Controllers/Public/QuizController.php:159`, outside the transaction and inside a `try/catch`. A mail failure cannot roll back a lead. Verified live against a dead SMTP port: lead intact, HTTP 302, result page 200, both failures logged with the SMTP error.
* **Step isolation.** `NotificationService::safely()` (`:57`) means a dashboard-notification failure cannot cancel the emails, and a failing sales email cannot cancel the customer's.
* **Server-side scoring.** The browser submits only option keys; points are read from the database and clamped (`ScoringService::evaluate`), so the score cannot be forged.
* **No queue anywhere in the path.** No `ShouldQueue`, `Mail::queue()` or `dispatch()`; verified live — `jobs` and `failed_jobs` stayed empty after real submissions.
* **Admin protection.** All 66 `admin/*` routes carry auth middleware; platform configuration additionally requires a manager role; deactivated accounts are rejected at login and mid-session.
* **Injection and XSS.** No `$request->all()` into `create`/`update`; every `selectRaw` is a static string with no interpolation; filters bind through `when()`; only two unescaped Blade echoes exist and one is a static icon map.
* **Secrets.** `.env` was never committed; the only secret-shaped value in git history is the literal string `null` from Laravel's default `.env.example`; `.env.example` ships empty placeholders.
* **Duplicate submissions.** One lead per session per 10 minutes, returning the original result.
* **Honeypot + CSRF** on the public form, and the result page is readable only by the submitting session or via a signed URL.

---

## Gaps — functionality that was never built

Ordered by how much they matter before taking real customers.

1. **No alert when email delivery starts failing.** Failures land in `notification_logs`, but nobody is told. If Gmail blocks the account mid-event, the first sign is a sales rep noticing silence. A daily digest, or a dashboard banner when failures exceed a threshold, is missing.
2. **No retry for failed sends.** `Admin → Email logs` has a manual resend button; there is no automatic retry, so a transient SMTP blip means that customer simply never receives their result.
3. **Google OAuth callback is untested and unexercised.** `app/Http/Controllers/Admin/GoogleAuthController.php` has no test coverage at all — no test hits `/auth/google/callback`. Domain restriction and the "no auto-register" rule are unverified.
4. **No lead deduplication.** The same person submitting from a different device creates a second lead. There is no matching on WhatsApp or email, so the sales list will contain duplicates from an event.
5. **No bounce or delivery feedback.** "Sent" means the SMTP server accepted the message, not that it arrived. Bounces are invisible.
6. **No admin-side lead editing.** Sales can change status, assign an owner and add notes, but cannot correct a typo'd phone number or email — which is precisely what H1 produces.
7. **No backup or export automation.** Excel export is manual and leaves no audit record (see M5).
8. **Untested failure paths.** Specifically: no matching result rule (M3), phone normalisation edges (H1), a failing `notification_logs` insert (L1), and database failure during lead creation.

---

## Suggested order of work

1. **H1** — validate the normalised phone; unreachable leads are worthless. (Also add the admin-side edit from Gap 6.)
2. **H2** — re-key the rate limiters by session before the next event.
3. **H3** — move the test guard to `beforeRefreshingDatabase()`.
4. **M1** — set `MAIL_TIMEOUT=8`; keep sending synchronous.
5. **M2, M3** — stop reporting simulated sends as delivered; log and fall back when no result rule matches.
6. **M5** — log admin sign-ins and lead exports.
7. **Gaps 1 and 2** — failure alerting and automatic retry.
8. **M4, M6, L1–L5** — cleanup and hardening as time allows.
